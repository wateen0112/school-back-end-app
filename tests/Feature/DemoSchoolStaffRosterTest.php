<?php

namespace Tests\Feature;

use App\Models\PaperExam;
use App\Models\PendingRequest;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Pending\PendingRequestService;
use App\User;
use Database\Seeders\DemoSchoolStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The demo roster has to be able to demonstrate the approval queue, so this walks the
 * exact walkthrough a reviewer would do in the browser: a module owner raises a request,
 * the wrong approver is refused, the right one decides, and the money/records only move
 * at that point.
 */
class DemoSchoolStaffRosterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        // The legacy seeders live in the global namespace, so the leading backslash
        // matters: without it Laravel looks for Database\Seeders\UserSeeder.
        $this->seed('\UserSeeder');
        $this->seed(DemoSchoolStaffSeeder::class);

        $this->school = School::where('email', 'admin@school.local')->firstOrFail();
    }

    public function test_every_account_belongs_to_the_demo_school(): void
    {
        $users = User::where('email', 'like', '%@school.local')
            ->where('is_super_admin', false)
            ->get();

        $this->assertCount(12, $users, 'The roster should hold twelve school accounts.');

        foreach ($users as $user) {
            $this->assertSame(
                $this->school->id,
                (int) $user->school_id,
                $user->email.' must belong to the demo school.'
            );

            $this->assertCount(
                1,
                $user->roles,
                $user->email.' must hold exactly one role, otherwise the requester/reviewer split is not reproducible.'
            );
        }
    }

    public function test_seeding_twice_does_not_duplicate_anything(): void
    {
        $this->seed(DemoSchoolStaffSeeder::class);

        $this->assertSame(12, User::where('email', 'like', '%@school.local')->where('is_super_admin', false)->count());
        $this->assertSame(12, Role::where('school_id', $this->school->id)->count());
    }

    public function test_the_module_owner_queues_and_the_module_reviewer_approves(): void
    {
        $teacher = $this->makeTeacher();

        // 1. The HR manager edits a teacher: queued, nothing applied yet.
        $queued = $this->actingAs($this->user('hr@school.local'), 'api')
            ->putJson("/api/teachers/{$teacher->id}", $this->teacherPayload($teacher, 9999))
            ->assertStatus(202)
            ->json('data.pending_request');

        $this->assertSame('update_teacher', $queued['action']);
        $this->assertNotSame(9999, (float) $teacher->fresh()->salary, 'Queueing must not touch the record.');

        $id = $queued['id'];

        // 2. The Dean of Studies cannot decide a teacher request, and neither can the requester.
        $this->actingAs($this->user('dean@school.local'), 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(403);

        $this->actingAs($this->user('hr@school.local'), 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(403);

        $this->assertSame('pending', PendingRequest::findOrFail($id)->status);

        // 3. The Registrar is the approver for this module: the edit lands.
        $this->actingAs($this->user('registrar@school.local'), 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(200);

        $this->assertSame(9999.0, (float) $teacher->fresh()->salary);
        $this->assertSame('approved', PendingRequest::findOrFail($id)->status);
    }

    public function test_the_blanket_approver_and_the_administrator_run_actions_directly(): void
    {
        $teacher = $this->makeTeacher();
        $payload = $this->teacherPayload($teacher, 7777);

        foreach (['principal@school.local', 'admin@school.local'] as $email) {
            $this->actingAs($this->user($email), 'api')
                ->putJson("/api/teachers/{$teacher->id}", $payload)
                ->assertStatus(200)
                ->assertJsonMissingPath('data.pending_request');
        }

        $this->assertSame(0, PendingRequest::where('action', 'update_teacher')->count());
    }

    public function test_a_module_reviewer_only_sees_their_own_queue(): void
    {
        $teacher = $this->makeTeacher();

        $exam = $this->makeExam();

        $this->actingAs($this->user('hr@school.local'), 'api')
            ->putJson("/api/teachers/{$teacher->id}", $this->teacherPayload($teacher, 8888))
            ->assertStatus(202);

        $this->actingAs($this->user('academic@school.local'), 'api')
            ->postJson("/api/paper-exams/{$exam->id}/publish")
            ->assertStatus(202);

        // The Registrar is scoped to teachers, the Dean to marks.
        $registrar = $this->actionsOf('registrar@school.local');
        $dean = $this->actionsOf('dean@school.local');
        $principal = $this->actionsOf('principal@school.local');

        $this->assertSame(['update_teacher'], $registrar);
        $this->assertSame(['send_marks'], $dean);
        $this->assertEqualsCanonicalizing(['send_marks', 'update_teacher'], $principal);
    }

    public function test_the_read_only_observer_is_shut_out_everywhere(): void
    {
        $viewer = $this->user('viewer@school.local');

        $this->assertTrue($viewer->hasPermission('dashboard.view'));
        $this->assertFalse($viewer->hasPermission('salaries.manage'));
        $this->assertFalse($viewer->hasPermission('teachers.manage'));
        $this->assertFalse(PendingRequestService::canAccessQueue($viewer));

        // Reads are deliberately open to any signed-in account in this API; the writes are
        // what the module permission guards, and the observer must not even be able to
        // *queue* one.
        $this->actingAs($viewer, 'api')->getJson('/api/salaries')->assertStatus(200);
        $this->actingAs($viewer, 'api')->getJson('/api/teachers')->assertStatus(200);
        $this->actingAs($viewer, 'api')
            ->putJson('/api/teachers/'.$this->makeTeacher()->id, $this->teacherPayload($this->makeTeacher(), 1))
            ->assertStatus(403);
        $this->actingAs($viewer, 'api')
            ->postJson('/api/salaries/'.$this->makeSalaryPayment()->id.'/pay')
            ->assertStatus(403);
        $this->actingAs($viewer, 'api')->getJson('/api/pending-requests?scope=reviewable')->assertStatus(403);

        $this->assertSame(0, PendingRequest::count(), 'A refused caller must not leave a request behind.');
    }

    public function test_an_unrelated_module_manager_cannot_reach_the_finance_queue(): void
    {
        $transport = $this->user('transport@school.local');

        $this->assertTrue($transport->hasPermission('transportation.buses.manage'));
        $this->assertFalse($transport->hasPermission('salaries.manage'));
        $this->assertFalse(PendingRequestService::canAccessQueue($transport));

        $this->actingAs($transport, 'api')->getJson('/api/salaries')->assertStatus(200);
        $this->actingAs($transport, 'api')
            ->postJson('/api/salaries/'.$this->makeSalaryPayment()->id.'/pay')
            ->assertStatus(403);
        $this->actingAs($transport, 'api')->getJson('/api/pending-requests?scope=reviewable')->assertStatus(403);

        $this->assertSame(0, PendingRequest::count());
    }

    public function test_the_finance_pair_can_pay_a_salary_between_them(): void
    {
        $payment = $this->makeSalaryPayment();

        // The Finance Officer holds salaries.manage but not salaries.approve: queued.
        $this->actingAs($this->user('finance@school.local'), 'api')
            ->postJson("/api/salaries/{$payment->id}/pay")
            ->assertStatus(202);

        $this->assertSame('pending', $payment->fresh()->status, 'The money must not move on queueing.');

        $request = PendingRequest::where('action', 'pay_salary')->latest('id')->firstOrFail();

        // The Finance Director approves it and the executor replays the real endpoint logic.
        $this->actingAs($this->user('cfo@school.local'), 'api')
            ->postJson("/api/pending-requests/{$request->id}/approve")
            ->assertStatus(200);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('approved', $request->fresh()->status);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /**
     * @return array<int, string>
     */
    private function actionsOf(string $email): array
    {
        $user = $this->user($email);

        return PendingRequest::pending()
            ->get()
            ->filter(fn (PendingRequest $request) => PendingRequestService::canReview($user, $request))
            ->map(fn (PendingRequest $request) => $request->action)
            ->values()
            ->all();
    }

    private function makeTeacher(): Teacher
    {
        $teacher = new Teacher();
        $teacher->Name = ['en' => 'Demo Teacher', 'ar' => 'معلم تجريبي'];
        $teacher->email = 'roster.teacher'.uniqid().'@school.local';
        $teacher->password = bcrypt('secret123');
        $teacher->Specialization_id = $this->specializationId();
        $teacher->Gender_id = $this->genderId();
        $teacher->Joining_Date = '2024-01-01';
        $teacher->Address = 'Somewhere';
        $teacher->salary = 3000;
        $teacher->school_id = $this->school->id;
        $teacher->save();

        return $teacher;
    }

    private function makeExam(): PaperExam
    {
        $subject = Subject::firstOrCreate(['Name' => 'Math']);

        return PaperExam::create([
            'title'        => 'Midterm',
            'subject_id'   => $subject->id,
            'teacher_id'   => $this->makeTeacher()->id,
            'grade_id'     => $this->gradeId(),
            'classroom_id' => $this->classroomId(),
            'section_id'   => $this->sectionId(),
            'exam_date'    => '2026-10-15',
            'status'       => 'draft',
        ]);
    }

    private function gradeId(): int
    {
        return \App\Models\Grade::firstOrCreate(['Name' => 'Grade 1'])->id;
    }

    private function classroomId(): int
    {
        return \App\Models\Classroom::firstOrCreate([
            'Name_Class' => 'Room 1',
            'Grade_id'   => $this->gradeId(),
        ])->id;
    }

    private function sectionId(): int
    {
        return \App\Models\Section::firstOrCreate([
            'Name_Section' => 'Section 1',
            'Class_id'     => $this->classroomId(),
            'Grade_id'     => $this->gradeId(),
            'Status'       => 1,
        ])->id;
    }

    private function makeSalaryPayment(): SalaryPayment    {
        return SalaryPayment::create([
            'employee_type' => 'staff',
            'employee_id'   => $this->user('finance@school.local')->id,
            'employee_name' => 'Finance Officer',
            'period_year'   => 2026,
            'period_month'  => 10,
            'amount'        => 4200,
            'due_date'      => '2026-10-28',
            'status'        => 'pending',
        ]);
    }

    private function specializationId(): int
    {
        if (! $id = \DB::table('specializations')->value('id')) {
            $id = \DB::table('specializations')->insertGetId(['Name' => json_encode(['en' => 'Math', 'ar' => 'رياضيات'])]);
        }

        return (int) $id;
    }

    private function genderId(): int
    {
        if (! $id = \DB::table('genders')->value('id')) {
            $id = \DB::table('genders')->insertGetId(['Name' => json_encode(['en' => 'Male', 'ar' => 'ذكر'])]);
        }

        return (int) $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function teacherPayload(Teacher $teacher, float $salary): array
    {
        return [
            'Email'             => $teacher->email,
            'Name_ar'           => $teacher->getTranslations('Name')['ar'],
            'Name_en'           => $teacher->getTranslations('Name')['en'],
            'Specialization_id' => $teacher->Specialization_id,
            'Gender_id'         => $teacher->Gender_id,
            'Joining_Date'      => '2024-01-01',
            'Address'           => $teacher->Address,
            'Salary'            => $salary,
        ];
    }
}
