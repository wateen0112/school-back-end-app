<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Grade;
use App\Models\PaperExam;
use App\Models\PaperExamMark;
use App\Models\PendingRequest;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentMonthlyReport;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Pending\PendingActionRegistry;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The approver's "show me what this will actually do" screen.
 *
 * The queue used to expose only a title and a reason, which made bulk actions
 * unreviewable in practice: signing off "send the monthly reports" fired
 * notifications for every student in the school, none of them visible. These
 * tests pin down three things:
 *
 *   1. the preview shows the same numbers the executor will write,
 *   2. it never writes anything itself,
 *   3. it is guarded exactly like `show()` — no cross-school, no self-approval
 *      by stealth, and no preview for an action the caller may not review.
 */
class PendingRequestPreviewTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private School $otherSchool;
    private User $requester;   // may generate reports, may not approve them
    private User $reviewer;    // may approve reports

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'School A', 'email' => 'a@example.test']);
        $this->otherSchool = School::create(['name' => 'School B', 'email' => 'b@example.test']);

        $this->requester = $this->userWith(['student_reports.manage' => true], $this->school);
        $this->reviewer = $this->userWith(
            ['student_reports.manage' => true, 'student_reports.approve' => true],
            $this->school
        );
    }

    // ------------------------------------------------------------------
    // What the approver gets to see
    // ------------------------------------------------------------------

    public function test_the_preview_lists_every_student_the_request_would_reach(): void
    {
        $this->makeStudentsWithData($this->school, 3);

        $id = $this->queueReportRequest($this->requester);

        $response = $this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200);

        $preview = $response->json('data.preview');

        $this->assertTrue($preview['available']);
        $this->assertSame(3, $this->summaryValue($preview, 'total'), 'Every student must be accounted for.');
        $this->assertSame(3, $this->summaryValue($preview, 'will_send'));
        $this->assertSame(6, $this->summaryValue($preview, 'notifications'), 'Student + parent per report.');
        $this->assertCount(3, $preview['rows']);
        $this->assertSame(3, $preview['meta']['total']);
    }

    public function test_the_preview_matches_the_numbers_the_executor_writes(): void
    {
        $students = $this->makeStudentsWithData($this->school, 2);

        $id = $this->queueReportRequest($this->requester);

        $rows = collect($this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview.rows'))
            ->keyBy('student_id');

        $this->actingAs($this->reviewer, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(200);

        $this->assertSame(2, StudentMonthlyReport::count());

        foreach ($students as $student) {
            $shown = $rows->get($student->id);
            $written = StudentMonthlyReport::where('student_id', $student->id)->firstOrFail();

            $this->assertNotNull($shown, 'The preview must include every student.');

            // The whole point of the screen: what is shown is what gets sent.
            $this->assertSame((float) $shown['score'], (float) $written->progress_score);
            $this->assertSame((float) $shown['attendance']['rate'], (float) $written->attendance_rate);
            $this->assertSame((int) $shown['attendance']['present'], (int) $written->attendance_present);
            $this->assertSame((int) $shown['attendance']['absent'], (int) $written->attendance_absent);
            $this->assertSame((int) $shown['attendance']['total_days'], (int) $written->attendance_total_days);
            $this->assertSame((float) $shown['exams']['overall_percentage'], (float) $written->exam_overall_percentage);
            $this->assertSame($shown['evaluations']['total'], (int) $written->evaluations_total);
            $this->assertSame($shown['quizzes']['total'], (int) $written->quizzes_total);
            $this->assertSame($shown['trend'], $written->trend);
        }
    }

    public function test_opening_the_preview_writes_nothing(): void
    {
        $this->makeStudentsWithData($this->school, 3);

        $id = $this->queueReportRequest($this->requester);

        $this->actingAs($this->reviewer, 'api')->getJson("/api/pending-requests/{$id}/preview")->assertStatus(200);
        $this->actingAs($this->reviewer, 'api')->getJson("/api/pending-requests/{$id}/preview")->assertStatus(200);

        $this->assertSame(0, StudentMonthlyReport::count(), 'Previewing must not create reports.');
        $this->assertSame(
            PendingRequest::STATUS_PENDING,
            PendingRequest::find($id)->status,
            'Previewing must not decide the request.'
        );
    }

    public function test_students_without_data_are_shown_as_skipped_not_hidden(): void
    {
        $this->makeStudentsWithData($this->school, 2);

        // A third student exists but has no attendance, evaluations, marks or quizzes.
        $empty = $this->makeStudent($this->school, 'Silent Child');

        $id = $this->queueReportRequest($this->requester);

        $preview = $this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview');

        $this->assertSame(3, $this->summaryValue($preview, 'total'));
        $this->assertSame(2, $this->summaryValue($preview, 'will_send'));
        $this->assertSame(1, $this->summaryValue($preview, 'skipped'));

        $row = collect($preview['rows'])->firstWhere('student_id', $empty->id);

        $this->assertNotNull($row, 'A student who would be skipped must still be listed.');
        $this->assertFalse($row['will_send'], 'The approver must see that no report would go out.');
        $this->assertNull($row['score']);

        $this->assertNotEmpty($preview['warnings'], 'A partial run deserves a warning.');
    }

    public function test_low_attendance_is_surfaced_as_a_warning(): void
    {
        // 20 days, 10 present: exactly 50%.
        $student = $this->makeStudent($this->school, 'Often Absent');
        $this->addAttendance($student, 10, 20);

        $id = $this->queueReportRequest($this->requester);

        $warnings = collect($this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview.warnings'))
            ->pluck('message')
            ->implode(' ');

        $this->assertStringContainsString('80%', $warnings);
    }

    public function test_the_preview_paginates_without_hiding_students(): void
    {
        $this->makeStudentsWithData($this->school, 5);

        $id = $this->queueReportRequest($this->requester);

        $first = $this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview?per_page=2&page=1")
            ->assertStatus(200)
            ->json('data.preview');

        $second = $this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview?per_page=2&page=2")
            ->assertStatus(200)
            ->json('data.preview');

        $this->assertCount(2, $first['rows']);
        $this->assertSame(3, $first['meta']['last_page']);
        $this->assertSame(5, $first['meta']['total'], 'The count must cover every page.');

        // Headline numbers are whole-set, not page-scoped.
        $this->assertSame(5, $this->summaryValue($first, 'total'));
        $this->assertSame(5, $this->summaryValue($second, 'total'));

        $ids = array_merge(
            array_column($first['rows'], 'student_id'),
            array_column($second['rows'], 'student_id')
        );
        $this->assertSame($ids, array_unique($ids), 'Pages must not repeat students.');
    }

    public function test_the_preview_can_be_searched(): void
    {
        $keep = $this->makeStudent($this->school, 'Findable One');
        $this->addAttendance($keep, 5, 5);
        $other = $this->makeStudent($this->school, 'Hidden One');
        $this->addAttendance($other, 5, 5);

        $id = $this->queueReportRequest($this->requester);

        $rows = $this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview?q=Findable")
            ->assertStatus(200)
            ->json('data.preview.rows');

        $this->assertCount(1, $rows);
        $this->assertSame($keep->id, $rows[0]['student_id']);
    }

    // ------------------------------------------------------------------
    // Tenancy
    // ------------------------------------------------------------------

    public function test_the_preview_never_reaches_another_schools_students(): void
    {
        $this->makeStudentsWithData($this->school, 2);
        $this->makeStudentsWithData($this->otherSchool, 3);

        $id = $this->queueReportRequest($this->requester);

        $preview = $this->actingAs($this->reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview');

        $this->assertSame(2, $this->summaryValue($preview, 'total'), 'Only this school\'s students.');

        $scopedIds = Student::where('school_id', $this->school->id)->pluck('id')->all();
        foreach (array_column($preview['rows'], 'student_id') as $studentId) {
            $this->assertContains($studentId, $scopedIds, 'A foreign student leaked into the preview.');
        }

        // And approving must not touch them either.
        $this->actingAs($this->reviewer, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(200);

        $this->assertSame(2, StudentMonthlyReport::count(), 'Approval must stay inside the school.');
    }

    public function test_a_request_targeting_another_schools_student_is_refused(): void
    {
        $foreign = $this->makeStudentsWithData($this->otherSchool, 1)[0];

        // The controller rejects it outright: a report for another school's
        // student must never enter the queue in the first place.
        $this->actingAs($this->requester, 'api')
            ->postJson('/api/student-reports/generate', [
                'month' => 5, 'year' => 2026, 'student_id' => $foreign->id,
            ])
            ->assertStatus(422);

        $this->assertSame(
            0,
            PendingRequest::where('action', PendingActionRegistry::SEND_REPORT)->count(),
            'No request may be queued for a foreign student.'
        );
    }

    public function test_a_queued_foreign_student_request_cannot_be_approved(): void
    {
        $foreign = $this->makeStudentsWithData($this->otherSchool, 1)[0];

        // Queued directly, as if it slipped in before validation existed: the
        // executor has to refuse it on approval too.
        $pending = PendingRequest::create([
            'action'         => PendingActionRegistry::SEND_REPORT,
            'school_id'      => $this->school->id,
            'requested_by_type' => User::class,
            'requested_by_id' => $this->requester->id,
            'entity_type'    => Student::class,
            'entity_id'      => $foreign->id,
            'title'          => 'Send monthly report of '.$foreign->name,
            'payload'        => ['month' => 5, 'year' => 2026, 'student_id' => $foreign->id],
            'status'         => PendingRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->reviewer, 'api')
            ->postJson("/api/pending-requests/{$pending->id}/approve")
            ->assertStatus(409);

        $this->assertSame(0, StudentMonthlyReport::count(), 'Nothing may be generated.');
        $this->assertSame(
            PendingRequest::STATUS_PENDING,
            $pending->fresh()->status,
            'The request must stay open for the approver to reject.'
        );
    }

    // ------------------------------------------------------------------
    // Access control
    // ------------------------------------------------------------------

    public function test_an_approver_from_another_school_cannot_read_the_preview(): void
    {
        $this->makeStudentsWithData($this->school, 2);
        $id = $this->queueReportRequest($this->requester);

        $stranger = $this->userWith(
            ['student_reports.manage' => true, 'student_reports.approve' => true],
            $this->otherSchool
        );

        $this->actingAs($stranger, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(403);
    }

    public function test_a_user_without_the_approval_permission_cannot_read_the_preview(): void
    {
        $this->makeStudentsWithData($this->school, 2);
        $id = $this->queueReportRequest($this->requester);

        // Same school, may queue reports, may not approve them, and is not the
        // requester — so nothing entitles them to the target list.
        $clerk = $this->userWith(['student_reports.manage' => true], $this->school);

        $this->actingAs($clerk, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(403);
    }

    public function test_the_requester_may_read_the_preview_of_their_own_request(): void
    {
        $this->makeStudentsWithData($this->school, 2);
        $id = $this->queueReportRequest($this->requester);

        // Owning the request is enough: it is the requester's own submission,
        // exactly as `show()` already allows.
        $this->actingAs($this->requester, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200);
    }

    public function test_a_foreign_approver_without_any_permission_is_refused(): void
    {
        $this->makeStudentsWithData($this->school, 2);
        $id = $this->queueReportRequest($this->requester);

        $clerk = User::factory()->create(['school_id' => $this->school->id]);
        $this->grant($clerk, ['student_reports.manage' => true]);

        $this->actingAs($clerk, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(403);
    }

    public function test_previewing_an_unknown_request_is_a_404(): void
    {
        $this->actingAs($this->reviewer, 'api')
            ->getJson('/api/pending-requests/999999/preview')
            ->assertStatus(404);
    }

    // ------------------------------------------------------------------
    // The other sensitive actions
    // ------------------------------------------------------------------

    public function test_the_teacher_preview_shows_a_before_and_after_diff(): void
    {
        $teacher = $this->makeTeacher($this->school);

        $clerk = $this->userWith(['teachers.manage' => true], $this->school);
        $this->actingAs($clerk, 'api')
            ->putJson("/api/teachers/{$teacher->id}", [
                'Name_ar' => 'جون الجديد',
                'Email' => 'new'.uniqid().'@example.test',
                'Specialization_id' => $teacher->Specialization_id,
                'Gender_id' => $teacher->Gender_id,
                'Joining_Date' => '2024-01-01',
                'Address' => 'Brand new address',
            ])
            ->assertStatus(202);

        $id = PendingRequest::where('action', PendingActionRegistry::UPDATE_TEACHER)->value('id');

        $reviewer = $this->userWith(['teachers.manage' => true, 'teachers.approve' => true], $this->school);

        $preview = $this->actingAs($reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview');

        $address = collect($preview['rows'])->firstWhere('field', 'Address');

        $this->assertNotNull($address, 'The submitted fields must be listed.');
        $this->assertSame('Old address 1', $address['before'], 'The current value must be readable.');
        $this->assertSame('Brand new address', $address['after']);
        $this->assertTrue($address['changed']);

        $this->assertSame('Old address 1', $teacher->fresh()->Address, 'Previewing must not apply anything.');
    }

    public function test_the_salary_preview_exposes_the_amount_being_released(): void
    {
        $payment = SalaryPayment::create([
            'employee_type' => 'staff',
            'employee_id'   => $this->requester->id,
            'period_year'   => 2026,
            'period_month'  => 3,
            'amount'        => 2750,
            'due_date'      => '2026-03-28',
            'status'        => 'pending',
        ]);

        $clerk = $this->userWith(['salaries.manage' => true], $this->school);
        $this->actingAs($clerk, 'api')
            ->postJson("/api/salaries/{$payment->id}/pay")
            ->assertStatus(202);

        $id = PendingRequest::where('action', PendingActionRegistry::PAY_SALARY)->value('id');

        $approver = $this->userWith(['salaries.manage' => true, 'salaries.approve' => true], $this->school);

        $preview = $this->actingAs($approver, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview');

        $this->assertSame(2750.0, (float) $this->summaryValue($preview, 'amount'));
        $this->assertSame('pending', $payment->fresh()->status, 'Previewing must not pay.');
    }

    public function test_a_marks_preview_lists_every_student_score(): void
    {
        $exam = $this->makeExam($this->school);
        $students = $this->makeStudentsWithData($this->school, 2);

        foreach ($students as $index => $student) {
            PaperExamMark::create([
                'paper_exam_id' => $exam->id,
                'student_id'    => $student->id,
                'marks_obtained' => $index === 0 ? 45 : 95,
            ]);
        }

        $clerk = $this->userWith(['paper_exams.manage' => true], $this->school);
        $this->actingAs($clerk, 'api')
            ->postJson("/api/paper-exams/{$exam->id}/publish")
            ->assertStatus(202);

        $id = PendingRequest::where('action', PendingActionRegistry::SEND_MARKS)->value('id');

        $approver = $this->userWith(['paper_exams.manage' => true, 'paper_exams.approve' => true], $this->school);

        $preview = $this->actingAs($approver, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview');

        $this->assertSame(2, $this->summaryValue($preview, 'marks'));
        $this->assertCount(2, $preview['rows']);
        $this->assertSame('draft', $exam->fresh()->status, 'Previewing must not publish.');
    }

    public function test_a_preview_of_a_deleted_target_reports_it_instead_of_crashing(): void
    {
        $teacher = $this->makeTeacher($this->school);

        $clerk = $this->userWith(['teachers.manage' => true], $this->school);
        $this->actingAs($clerk, 'api')
            ->putJson("/api/teachers/{$teacher->id}", [
                'Name_ar' => 'جون',
                'Email' => 'x'.uniqid().'@example.test',
                'Specialization_id' => $teacher->Specialization_id,
                'Gender_id' => $teacher->Gender_id,
                'Joining_Date' => '2024-01-01',
                'Address' => 'Somewhere else',
            ])
            ->assertStatus(202);

        $id = PendingRequest::where('action', PendingActionRegistry::UPDATE_TEACHER)->value('id');

        $teacher->delete();

        $reviewer = $this->userWith(['teachers.manage' => true, 'teachers.approve' => true], $this->school);

        $preview = $this->actingAs($reviewer, 'api')
            ->getJson("/api/pending-requests/{$id}/preview")
            ->assertStatus(200)
            ->json('data.preview');

        $this->assertFalse($preview['available']);
        $this->assertNotEmpty($preview['warnings']);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array<int, Student>
     */
    private function makeStudentsWithData(School $school, int $count): array
    {
        $students = [];

        for ($i = 0; $i < $count; $i++) {
            $student = $this->makeStudent($school, 'Student '.$i);
            // 20 days, 18 present: 90%.
            $this->addAttendance($student, 18, 20);
            $students[] = $student;
        }

        return $students;
    }

    private function makeStudent(School $school, string $name): Student
    {
        $grade = Grade::firstOrCreate(['Name' => 'Grade 1']);

        // `students` has no defaultable NOT NULL columns, so a fixture has to
        // supply every one of them.
        return Student::create([
            'name'          => ['en' => $name, 'ar' => $name],
            'email'         => str_replace(' ', '.', strtolower($name)).uniqid().'@example.test',
            'password'      => bcrypt('secret123'),
            'gender_id'     => \App\Models\Gender::firstOrCreate(['Name' => 'Male'])->id,
            'nationalitie_id' => \App\Models\Nationalitie::firstOrCreate(['Name' => 'Egyptian'])->id,
            'blood_id'      => $this->bloodId(),
            'Date_Birth'    => '2012-01-15',
            'Grade_id'      => $grade->id,
            'Classroom_id'  => \App\Models\Classroom::firstOrCreate([
                'Name_Class' => 'Room 1', 'Grade_id' => $grade->id,
            ])->id,
            'section_id'    => Section::firstOrCreate([
                'Name_Section' => 'Section 1',
                'Status'       => 1,
                'Grade_id'     => $grade->id,
                'Class_id'     => \App\Models\Classroom::firstOrCreate([
                    'Name_Class' => 'Room 1', 'Grade_id' => $grade->id,
                ])->id,
            ])->id,
            'academic_year' => '2025/2026',
            'school_id'     => $school->id,
        ]);
    }

    /**
     * `$present` present days out of `$total`.
     */
    private function addAttendance(Student $student, int $present, int $total): void
    {
        // One teacher reused for the whole fixture.
        $teacherId = $this->makeTeacher($this->school)->id;

        for ($day = 1; $day <= $total; $day++) {
            Attendance::create([
                'student_id'        => $student->id,
                'grade_id'          => $student->Grade_id,
                'classroom_id'      => $student->Classroom_id,
                'section_id'        => $student->section_id,
                'teacher_id'        => $teacherId,
                'attendence_date'   => sprintf('2026-05-%02d', $day),
                'attendence_status' => $day <= $present,
            ]);
        }
    }

    /**
     * Queue a bulk "send the monthly reports" request the way the UI does.
     */
    private function queueReportRequest(User $user, int $month = 5, int $year = 2026): int
    {
        $this->actingAs($user, 'api')
            ->postJson('/api/student-reports/generate', ['month' => $month, 'year' => $year])
            ->assertStatus(202);

        return (int) PendingRequest::where('action', PendingActionRegistry::SEND_REPORT)->value('id');
    }

/**
     * `type__bloods` has no model in app/Models, so seed it directly.
     */
    private function bloodId(): int
    {
        $existing = \Illuminate\Support\Facades\DB::table('type__bloods')->where('Name', 'O+')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) \Illuminate\Support\Facades\DB::table('type__bloods')->insertGetId(['Name' => 'O+']);
    }

    private function summaryValue(array $preview, string $key)
    {
        $item = collect($preview['summary'])->firstWhere('key', $key);

        return $item['value'] ?? null;
    }

    private function userWith(array $permissions, School $school): User
    {
        $user = User::factory()->create(['school_id' => $school->id]);
        $this->grant($user, $permissions);

        return $user->fresh();
    }

    private function grant(User $user, array $permissions): void
    {
        $role = Role::create([
            'name'        => 'role-'.$user->id.'-'.substr(md5(json_encode($permissions)), 0, 6),
            'is_active'   => true,
            'school_id'   => $user->school_id,
            'permissions' => $permissions,
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    private function makeTeacher(School $school): Teacher
    {
        $teacher = new Teacher();
        $teacher->Name              = ['en' => 'John Doe', 'ar' => 'جون'];
        $teacher->email             = 'john'.uniqid().'@example.test';
        $teacher->password          = bcrypt('secret123');
        $teacher->Specialization_id = \App\Models\Specialization::firstOrCreate(['Name' => 'General'])->id;
        $teacher->Gender_id         = \App\Models\Gender::firstOrCreate(['Name' => 'Male'])->id;
        $teacher->Joining_Date      = '2024-01-01';
        $teacher->Address           = 'Old address 1';
        $teacher->school_id         = $school->id;
        $teacher->save();

        return $teacher;
    }

    private function makeExam(School $school): PaperExam
    {
        $grade = Grade::firstOrCreate(['Name' => 'Grade 1']);
        $classroom = \App\Models\Classroom::firstOrCreate(['Name_Class' => 'Room 1', 'Grade_id' => $grade->id]);
        $section = Section::firstOrCreate([
            'Name_Section' => 'Section 1',
            'Status'       => 1,
            'Grade_id'     => $grade->id,
            'Class_id'     => $classroom->id,
        ]);

        return PaperExam::create([
            'title'        => 'Midterm',
            'subject_id'   => Subject::firstOrCreate(['Name' => 'Math'])->id,
            'teacher_id'   => $this->makeTeacher($school)->id,
            'grade_id'     => $grade->id,
            'classroom_id' => $classroom->id,
            'section_id'   => $section->id,
            'exam_date'    => '2026-05-15',
            'max_score'    => 100,
            'status'       => 'draft',
        ]);
    }
}