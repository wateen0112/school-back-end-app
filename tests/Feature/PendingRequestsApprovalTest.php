<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\PaperExam;
use App\Models\PendingRequest;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The approval gate: a user without the action's approval permission must not
 * perform the action, only queue it; an approver may still act directly, and an
 * admin replaying a queued request must get exactly the same outcome.
 */
class PendingRequestsApprovalTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private School $otherSchool;
    private User $accountant;   // may manage salaries, may not pay them
    private User $approver;     // may pay salaries
    private SalaryPayment $salary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'School A', 'email' => 'a@example.test']);
        $this->otherSchool = School::create(['name' => 'School B', 'email' => 'b@example.test']);

        // May perform the *manage* half of every sensitive screen, but none of
        // the approvals: this is the user the queue exists for.
        $manage = [
            'salaries.manage'    => true,
            'teachers.manage'    => true,
            'paper_exams.manage' => true,
        ];

        $this->accountant = $this->userWith($manage, $this->school);
        $this->approver = $this->userWith($manage + ['salaries.approve' => true], $this->school);

        $this->salary = SalaryPayment::create([
            'employee_type' => 'staff',
            'employee_id'   => $this->accountant->id,
            'period_year'   => 2026,
            'period_month'  => 3,
            'amount'        => 1500,
            'due_date'      => '2026-03-28',
            'status'        => 'pending',
        ]);
    }

    // ------------------------------------------------------------------
    // Deferring
    // ------------------------------------------------------------------

    public function test_paying_a_salary_without_approval_rights_queues_the_request(): void
    {
        $response = $this->actingAs($this->accountant, 'api')
            ->postJson("/api/salaries/{$this->salary->id}/pay");

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.requires_approval', true);

        $this->assertSame('pending', $this->salary->fresh()->status, 'The salary must not be paid.');
        $this->assertNull($this->salary->fresh()->paid_at);

        $this->assertDatabaseHas('pending_requests', [
            'action'         => PendingActionRegistry::PAY_SALARY,
            'entity_type'    => SalaryPayment::class,
            'entity_id'      => $this->salary->id,
            'status'         => PendingRequest::STATUS_PENDING,
            'requested_by_id' => $this->accountant->id,
        ]);
    }

    public function test_queueing_the_same_action_twice_reuses_the_open_request(): void
    {
        $first = $this->payAs($this->accountant);
        $second = $this->payAs($this->accountant);

        $this->assertSame(
            $first->json('data.pending_request.id'),
            $second->json('data.pending_request.id'),
            'A second attempt must not create a duplicate queue entry.'
        );

        $this->assertSame(1, PendingRequest::count());
    }

    public function test_approver_pays_directly_and_creates_no_pending_request(): void
    {
        $this->payAs($this->approver)->assertStatus(200)->assertJsonPath('success', true);

        $this->assertSame('paid', $this->salary->fresh()->status);
        $this->assertSame(0, PendingRequest::count());
    }

    // ------------------------------------------------------------------
    // Reviewing
    // ------------------------------------------------------------------

    public function test_approving_a_queued_request_runs_the_action(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        $response = $this->actingAs($this->approver, 'api')
            ->postJson("/api/pending-requests/{$id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', PendingRequest::STATUS_APPROVED);

        $salary = $this->salary->fresh();
        $this->assertSame('paid', $salary->status);
        $this->assertNotNull($salary->paid_at);
        $this->assertSame($this->approver->id, $salary->paid_by);

        $this->assertSame(
            1,
            $salary->fundEntry()->count(),
            'Approving must write the same cash-out entry as paying directly.'
        );
    }

    public function test_approver_is_notified_when_a_request_is_queued(): void
    {
        $this->payAs($this->accountant);

        $this->assertTrue(
            $this->hasRecipient(User::class, $this->approver->id),
            'The approver must be notified about the queued request.'
        );
    }

    public function test_requester_is_notified_of_the_decision(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        $this->actingAs($this->approver, 'api')->postJson("/api/pending-requests/{$id}/approve")->assertStatus(200);

        $this->assertTrue(
            $this->hasRecipient(User::class, $this->accountant->id),
            'The requester must learn the outcome.'
        );
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        $this->actingAs($this->approver, 'api')
            ->postJson("/api/pending-requests/{$id}/reject")
            ->assertStatus(422);

        $this->assertSame(PendingRequest::STATUS_PENDING, PendingRequest::find($id)->status);
        $this->assertSame('pending', $this->salary->fresh()->status);
    }

    public function test_rejecting_with_a_reason_keeps_the_action_unperformed(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        $this->actingAs($this->approver, 'api')
            ->postJson("/api/pending-requests/{$id}/reject", ['notes' => 'Wrong amount on the payslip.'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', PendingRequest::STATUS_REJECTED);

        $this->assertSame('pending', $this->salary->fresh()->status);
        $this->assertDatabaseHas('pending_requests', [
            'id' => $id,
            'review_notes' => 'Wrong amount on the payslip.',
        ]);
    }

    public function test_a_reviewed_request_cannot_be_reviewed_again(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        $this->actingAs($this->approver, 'api')->postJson("/api/pending-requests/{$id}/approve")->assertStatus(200);

        $this->actingAs($this->approver, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(409);
    }

    // ------------------------------------------------------------------
    // Access control
    // ------------------------------------------------------------------

    public function test_the_requester_cannot_approve_their_own_request(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        // Promote a copy of the requester to approver: the point is that being
        // an approver is not enough, self-approval is refused.
        $this->grant($this->accountant, [
            'salaries.manage' => true,
            'salaries.approve' => true,
        ]);

        $this->actingAs($this->accountant, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(403);

        $this->assertSame('pending', $this->salary->fresh()->status);
    }

    public function test_approvers_from_another_school_cannot_see_or_review_the_request(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');
        $stranger = $this->userWith(['salaries.manage' => true, 'salaries.approve' => true], $this->otherSchool);
        $this->actingAs($stranger, 'api')->getJson('/api/pending-requests')->assertStatus(200);
        $this->assertSame([], $this->pagedIds('/api/pending-requests', $stranger), 'Cross-school leakage.');

        $this->actingAs($stranger, 'api')
            ->getJson("/api/pending-requests/{$id}")
            ->assertStatus(403);

        $this->actingAs($stranger, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(403);

        $this->assertSame('pending', $this->salary->fresh()->status);
    }

    public function test_a_partial_approver_only_sees_the_actions_they_own(): void
    {
        $salaryId = $this->payAs($this->accountant)->json('data.pending_request.id');

        $exam = $this->makeExam();
        $examId = $this->actingAs($this->accountant, 'api')
            ->postJson("/api/paper-exams/{$exam->id}/publish")
            ->assertStatus(202)
            ->json('data.pending_request.id');

        // `salaries.approve` only: the exam request must stay invisible.
        $ids = $this->pagedIds('/api/pending-requests', $this->approver);

        $this->assertContains((int) $salaryId, $ids);
        $this->assertNotContains((int) $examId, $ids, 'A partial approver must not see other queues.');
    }

    public function test_opening_the_queue_is_not_the_same_as_being_allowed_to_approve(): void
    {
        $id = (int) $this->payAs($this->accountant)->json('data.pending_request.id');

        // `pending_requests.manage` only reaches the queue screen; it is not an approval.
        $clerk = User::factory()->create(['school_id' => $this->school->id]);
        $this->grant($clerk, ['pending_requests.manage' => true]);

        $this->assertTrue(
            PendingRequestService::canAccessQueue($clerk),
            'A queue manager must be able to open the screen.'
        );

        $this->assertFalse(
            PendingRequestService::isReviewer($clerk),
            'A queue manager is not an approver.'
        );

        $this->assertFalse(
            PendingRequestService::canReview($clerk, PendingRequest::findOrFail($id)),
            'A queue manager must not be able to approve, otherwise the per-module keys are meaningless.'
        );

        $this->actingAs($clerk, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(403);

        $this->assertSame('pending', PendingRequest::findOrFail($id)->status);
        $this->assertSame('pending', $this->salary->fresh()->status, 'The salary must not have moved.');
    }

    public function test_stats_only_count_what_the_actor_may_review(): void
    {
        $this->payAs($this->accountant);

        $response = $this->actingAs($this->approver, 'api')->getJson('/api/pending-requests/stats');

        $response->assertStatus(200)->assertJsonPath('data.pending', 1);
    }

    public function test_every_listed_row_carries_its_review_flag_and_labels(): void
    {
        $id = (int) $this->payAs($this->accountant)->json('data.pending_request.id');

        $rows = $this->actingAs($this->approver, 'api')
            ->getJson('/api/pending-requests?status=pending')
            ->assertStatus(200)
            ->json('data');

        $this->assertCount(1, $rows);

        $row = $rows[0];

        // The queue and the dashboard widget both gate their approve / reject
        // buttons on these fields, so a list response must carry them per row.
        $this->assertSame($id, $row['id']);
        $this->assertTrue($row['can_review'], 'The approver must be able to review this row.');
        $this->assertSame('admin', $row['requester_role']);
        $this->assertNotEmpty($row['action_label']);
        $this->assertNotEmpty($row['title']);
        $this->assertSame($this->accountant->id, $row['requester']['id']);
    }

    public function test_the_requesters_own_row_is_not_marked_reviewable(): void
    {
        $this->payAs($this->accountant);

        $rows = $this->actingAs($this->accountant, 'api')
            ->getJson('/api/pending-requests?scope=mine')
            ->assertStatus(200)
            ->json('data');

        $this->assertCount(1, $rows);
        $this->assertFalse(
            $rows[0]['can_review'],
            'Seeing your own request must not expose the approve controls.'
        );
    }

    // ------------------------------------------------------------------
    // Conflicting state
    // ------------------------------------------------------------------

    public function test_approving_after_the_salary_was_already_paid_reports_a_conflict(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        // Somebody pays it in the meantime, so the queued request is stale.
        $this->actingAs($this->approver, 'api')
            ->postJson("/api/salaries/{$this->salary->id}/pay")
            ->assertStatus(200);

        $this->actingAs($this->approver, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(409);

        $this->assertSame(
            PendingRequest::STATUS_PENDING,
            PendingRequest::find($id)->status,
            'A conflict must leave the request open so the approver can act.'
        );

        // Exactly one cash-out entry, not two.
        $this->assertSame(1, $this->salary->fresh()->fundEntry()->count());
    }

    public function test_approving_after_the_target_was_deleted_reports_a_conflict(): void
    {
        $id = $this->payAs($this->accountant)->json('data.pending_request.id');

        $this->salary->delete();

        $this->actingAs($this->approver, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(409);

        $this->assertSame(PendingRequest::STATUS_PENDING, PendingRequest::find($id)->status);
    }

    // ------------------------------------------------------------------
    // Other sensitive actions
    // ------------------------------------------------------------------

    public function test_generating_monthly_reports_requires_approval(): void
    {
        $this->actingAs($this->accountant, 'api')
            ->postJson('/api/student-reports/generate', ['month' => 3, 'year' => 2026])
            ->assertStatus(202);

        $pending = PendingRequest::where('action', PendingActionRegistry::SEND_REPORT)->first();

        $this->assertNotNull($pending);
        $this->assertSame(3, $pending->payload['month']);
        $this->assertSame(2026, $pending->payload['year']);
        $this->assertSame(0, \App\Models\StudentMonthlyReport::count(), 'Nothing may be generated yet.');
    }

    public function test_a_report_request_needs_an_explicit_approver(): void
    {
        $this->actingAs($this->accountant, 'api')
            ->postJson('/api/student-reports/generate', ['month' => 3, 'year' => 2026])
            ->assertStatus(202);

        // The salary approver must not be able to sign off a report.
        $this->actingAs($this->approver, 'api')
            ->postJson('/api/pending-requests/'.PendingRequest::where('action', PendingActionRegistry::SEND_REPORT)->value('id').'/approve')
            ->assertStatus(403);

        $this->actingAs($this->userWith(['student_reports.approve' => true], $this->school), 'api')
            ->postJson('/api/pending-requests/'.PendingRequest::where('action', PendingActionRegistry::SEND_REPORT)->value('id').'/approve')
            ->assertStatus(200);
    }

    public function test_publishing_marks_requires_approval_and_replays_on_approval(): void
    {
        $exam = $this->makeExam();

        $this->actingAs($this->accountant, 'api')
            ->postJson("/api/paper-exams/{$exam->id}/publish")
            ->assertStatus(202);

        $this->assertNotSame('published', $exam->fresh()->status);

        $id = PendingRequest::where('action', PendingActionRegistry::SEND_MARKS)->value('id');

        $publisher = $this->userWith(['paper_exams.manage' => true, 'paper_exams.approve' => true], $this->school);

        $this->actingAs($publisher, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(200);

        $this->assertSame('published', $exam->fresh()->status);
    }

    public function test_editing_a_teacher_requires_approval_and_replays_on_approval(): void
    {
        $teacher = $this->makeTeacher();

        $this->actingAs($this->accountant, 'api')
            ->putJson("/api/teachers/{$teacher->id}", array_merge($this->teacherPayload(), ['Address' => 'New address 9']))
            ->assertStatus(202);

        $this->assertNotSame('New address 9', $teacher->fresh()->Address);

        $id = PendingRequest::where('action', PendingActionRegistry::UPDATE_TEACHER)->value('id');
        $reviewer = $this->userWith(['teachers.manage' => true, 'teachers.approve' => true], $this->school);

        $this->actingAs($reviewer, 'api')
            ->postJson("/api/pending-requests/{$id}/approve")
            ->assertStatus(200);

        $this->assertSame('New address 9', $teacher->fresh()->Address);
    }

    public function test_approver_edits_a_teacher_directly(): void
    {
        $teacher = $this->makeTeacher();
        $reviewer = $this->userWith(['teachers.manage' => true, 'teachers.approve' => true], $this->school);

        $this->actingAs($reviewer, 'api')
            ->putJson("/api/teachers/{$teacher->id}", array_merge($this->teacherPayload(), ['Address' => 'Direct edit']))
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSame('Direct edit', $teacher->fresh()->Address);
        $this->assertSame(0, PendingRequest::count());
    }

    public function test_a_school_less_actor_cannot_queue_a_request(): void
    {
        // The permission middleware lets teacher / student / parent tokens
        // through and none of them are approvers, so they reach defer(). A row
        // with no school must be refused rather than written as a request that
        // no approver could ever see.
        $schoolless = new Teacher();
        $schoolless->Name = ['en' => 'No School', 'ar' => 'بلا مدرسة'];
        $schoolless->email = 'noschool'.uniqid().'@example.test';
        $schoolless->password = bcrypt('secret123');
        $schoolless->Specialization_id = $this->specializationId();
        $schoolless->Gender_id = $this->genderId();
        $schoolless->Joining_Date = '2024-01-01';
        $schoolless->Address = 'Somewhere';
        $schoolless->school_id = null;
        $schoolless->save();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\ConflictHttpException::class);

        PendingRequestService::defer(PendingActionRegistry::PAY_SALARY, [
            'entity' => $this->salary,
            'title'  => 'Pay salary',
        ], $schoolless);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function payAs(User $user)
    {
        return $this->actingAs($user, 'api')->postJson("/api/salaries/{$this->salary->id}/pay");
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
            'name'       => 'role-'.$user->id.'-'.substr(md5(json_encode($permissions)), 0, 6),
            'is_active'  => true,
            'school_id'  => $user->school_id,
            'permissions' => $permissions,
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    private function pagedIds(string $url, ?User $as = null): array
    {
        $request = $as ? $this->actingAs($as, 'api') : $this;

        return collect($request->getJson($url)->json('data') ?? [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function hasRecipient(string $recipientModel, int $id): bool
    {
        return NotificationRecipient::where('recipient_type', $recipientModel)
            ->where('recipient_id', $id)
            ->whereIn('notification_id', Notification::select('id'))
            ->exists();
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
            'exam_date'    => '2026-03-15',
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
            'Status'       => 1,
            'Grade_id'     => $this->gradeId(),
            'Class_id'     => $this->classroomId(),
        ])->id;
    }

    private function makeTeacher(): Teacher
    {
        // Teacher is not mass-assignable, so mirror what the controller does.
        $teacher = new Teacher();
        $teacher->Name              = ['en' => 'John Doe', 'ar' => 'جون'];
        $teacher->email             = 'john'.uniqid().'@example.test';
        $teacher->password          = bcrypt('secret123');
        $teacher->Specialization_id = $this->specializationId();
        $teacher->Gender_id         = $this->genderId();
        $teacher->Joining_Date      = '2024-01-01';
        $teacher->Address           = 'Old address 1';
        $teacher->school_id         = $this->school->id;
        $teacher->save();

        return $teacher;
    }

    private function teacherPayload(): array
    {
        return [
            'Email'            => 'teacher'.uniqid().'@example.test',
            'Name_ar'          => 'جون',
            'Name_en'          => 'John Doe',
            'Specialization_id' => $this->specializationId(),
            'Gender_id'         => $this->genderId(),
            'Joining_Date'     => '2024-01-01',
            'Address'          => 'Queued address',
        ];
    }

    private function specializationId(): int
    {
        return \App\Models\Specialization::firstOrCreate(['Name' => 'General'])->id;
    }

    private function genderId(): int
    {
        return \App\Models\Gender::firstOrCreate(['Name' => 'Male'])->id;
    }
}