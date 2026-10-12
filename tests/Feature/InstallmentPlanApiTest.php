<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Fee;
use App\Models\Gender;
use App\Models\Grade;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentInstallment;
use App\Models\Type_Blood;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallmentPlanApiTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    private function createClassroomWithStudent()
    {
        $gender = Gender::create(['Name' => 'Male']);
        $nationality = Nationalitie::create(['Name' => 'Syrian']);
        $blood = Type_Blood::create(['Name' => 'O+']);
        $grade = Grade::create(['Name' => 'First Grade', 'Notes' => '']);
        $classroom = Classroom::create(['Name_Class' => '1-A', 'Grade_id' => $grade->id]);
        $fee = Fee::create([
            'title' => ['en' => 'Tuition', 'ar' => 'رسوم'],
            'amount' => 1000,
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'year' => '2026-2027',
            'Fee_type' => 1,
        ]);
        $sectionId = \DB::table('sections')->insertGetId([
            'Name_Section' => json_encode(['en' => 'A', 'ar' => 'أ']),
            'Grade_id' => $grade->id,
            'Class_id' => $classroom->id,
            'Status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $section = Section::find($sectionId);
        $student = Student::create([
            'name' => ['en' => 'Test Student', 'ar' => 'طالب تجريبي'],
            'email' => 'student' . rand(1000, 9999) . '@test.com',
            'password' => bcrypt('password'),
            'gender_id' => $gender->id,
            'nationalitie_id' => $nationality->id,
            'blood_id' => $blood->id,
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'section_id' => $section->id,
            'Date_Birth' => '2015-01-01',
            'academic_year' => '2026-2027',
        ]);

        return compact('grade', 'classroom', 'student', 'fee');
    }

    private function createStudentInstallments(Student $student, Fee $fee, array $overrides = [])
    {
        $plan = InstallmentPlan::create([
            'title' => ['ar' => 'خطة تجريبية', 'en' => 'Test Plan'],
            'description' => null,
            'grade_id' => $fee->Grade_id,
            'classroom_id' => $fee->Classroom_id,
            'fee_id' => $fee->id,
            'total_amount' => 1000,
            'academic_year' => '2026-2027',
        ]);

        $installment = Installment::create([
            'installment_plan_id' => $plan->id,
            'title' => 'الدفعة الأولى',
            'amount' => 1000,
            'due_date' => '2026-09-01',
            'sort_order' => 1,
        ]);

        $statuses = $overrides['statuses'] ?? ['pending', 'pending', 'pending', 'pending'];
        $dueDates = $overrides['due_dates'] ?? [
            now()->subDays(6)->format('Y-m-d'),
            now()->addDays(2)->format('Y-m-d'),
            now()->addDays(6)->format('Y-m-d'),
            now()->addDays(19)->format('Y-m-d'),
        ];
        $paidAmounts = $overrides['paid_amounts'] ?? [0, 0, 0, 0];

        $studentInstallments = [];
        foreach ($statuses as $index => $status) {
            $amount = 250;
            $paidAmount = $paidAmounts[$index] ?? 0;
            $remainingAmount = max(0, $amount - $paidAmount);

            $studentInstallments[] = StudentInstallment::create([
                'installment_plan_id' => $plan->id,
                'installment_id' => $installment->id,
                'student_id' => $student->id,
                'fee_invoice_id' => null,
                'amount' => $amount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => $status,
                'due_date' => $dueDates[$index],
            ]);
        }

        return $studentInstallments;
    }

    private function createParentWithStudent(Student $student)
    {
        $parent = My_Parent::create([
            'email' => 'parent' . rand(1000, 9999) . '@test.com',
            'password' => bcrypt('password'),
            'Name_Father' => 'Test Parent',
        ]);

        $student->update(['parent_id' => $parent->id]);

        return $parent;
    }

    #[Test]
    public function admin_can_create_installment_plan_with_installments()
    {
        $data = $this->createClassroomWithStudent();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/installment-plans', [
                'title' => ['ar' => 'خطة تجريبية', 'en' => 'Test Plan'],
                'description' => ['ar' => 'وصف', 'en' => 'Description'],
                'grade_id' => $data['grade']->id,
                'classroom_id' => $data['classroom']->id,
                'fee_id' => $data['fee']->id,
                'total_amount' => 1000,
                'academic_year' => '2026-2027',
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-01',
                'installments' => [
                    ['title' => 'الدفعة الأولى', 'amount' => 600, 'due_date' => '2026-09-01'],
                    ['title' => 'الدفعة الثانية', 'amount' => 400, 'due_date' => '2026-11-01'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_amount', 1000)
            ->assertJsonPath('data.installments_count', 2);

        $this->assertDatabaseHas('installment_plans', ['total_amount' => 1000]);
        $this->assertDatabaseHas('student_installments', [
            'student_id' => $data['student']->id,
            'amount' => 600,
        ]);
    }

    #[Test]
    public function it_rejects_installment_plan_when_sum_does_not_match_total()
    {
        $data = $this->createClassroomWithStudent();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/installment-plans', [
                'title' => ['ar' => 'خطة', 'en' => 'Plan'],
                'grade_id' => $data['grade']->id,
                'classroom_id' => $data['classroom']->id,
                'fee_id' => $data['fee']->id,
                'total_amount' => 1000,
                'academic_year' => '2026-2027',
                'installments' => [
                    ['title' => 'الدفعة الأولى', 'amount' => 500, 'due_date' => '2026-09-01'],
                    ['title' => 'الدفعة الثانية', 'amount' => 400, 'due_date' => '2026-11-01'],
                ],
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function admin_can_list_installment_plans()
    {
        $data = $this->createClassroomWithStudent();

        InstallmentPlan::create([
            'title' => ['ar' => 'خطة', 'en' => 'Plan'],
            'description' => null,
            'grade_id' => $data['grade']->id,
            'classroom_id' => $data['classroom']->id,
            'fee_id' => $data['fee']->id,
            'total_amount' => 1000,
            'academic_year' => '2026-2027',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/installment-plans');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function my_installments_returns_summary_for_student()
    {
        $data = $this->createClassroomWithStudent();
        $this->createStudentInstallments($data['student'], $data['fee'], [
            'statuses' => ['overdue', 'pending', 'paid', 'pending'],
            'due_dates' => [
                now()->subDays(6)->format('Y-m-d'),
                now()->addDays(2)->format('Y-m-d'),
                now()->addDays(6)->format('Y-m-d'),
                now()->addDays(19)->format('Y-m-d'),
            ],
            'paid_amounts' => [0, 0, 250, 0],
        ]);

        $response = $this->actingAs($data['student'], 'api')
            ->getJson('/api/my-installments');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_installments', 4)
            ->assertJsonPath('data.summary.paid_count', 1)
            ->assertJsonPath('data.summary.pending_count', 2)
            ->assertJsonPath('data.summary.partial_count', 0)
            ->assertJsonPath('data.summary.overdue_count', 1)
            ->assertJsonPath('data.summary.urgent_count', 2)
            ->assertJsonCount(4, 'data.installments')
            ->assertJsonPath('data.installments.0.urgency', 'urgent')
            ->assertJsonPath('data.installments.1.urgency', 'urgent')
            ->assertJsonPath('data.installments.2.urgency', 'normal')
            ->assertJsonPath('data.installments.3.urgency', 'normal');
    }

    #[Test]
    public function my_installments_can_be_filtered_by_status()
    {
        $data = $this->createClassroomWithStudent();
        $this->createStudentInstallments($data['student'], $data['fee'], [
            'statuses' => ['overdue', 'pending', 'paid', 'pending'],
            'due_dates' => [
                now()->subDays(6)->format('Y-m-d'),
                now()->addDays(2)->format('Y-m-d'),
                now()->addDays(6)->format('Y-m-d'),
                now()->addDays(19)->format('Y-m-d'),
            ],
            'paid_amounts' => [0, 0, 250, 0],
        ]);

        $response = $this->actingAs($data['student'], 'api')
            ->getJson('/api/my-installments?status=pending,overdue');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.installments')
            ->assertJsonPath('data.summary.total_installments', 4);

        $statuses = collect($response->json('data.installments'))->pluck('status')->all();
        $this->assertContains('pending', $statuses);
        $this->assertContains('overdue', $statuses);
        $this->assertNotContains('paid', $statuses);
    }

    #[Test]
    public function my_installments_can_be_filtered_by_urgency()
    {
        $data = $this->createClassroomWithStudent();
        $this->createStudentInstallments($data['student'], $data['fee'], [
            'statuses' => ['overdue', 'pending', 'paid', 'pending'],
            'due_dates' => [
                now()->subDays(6)->format('Y-m-d'),
                now()->addDays(2)->format('Y-m-d'),
                now()->addDays(6)->format('Y-m-d'),
                now()->addDays(19)->format('Y-m-d'),
            ],
            'paid_amounts' => [0, 0, 250, 0],
        ]);

        $response = $this->actingAs($data['student'], 'api')
            ->getJson('/api/my-installments?urgency=urgent');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.installments')
            ->assertJsonPath('data.summary.urgent_count', 2);

        foreach ($response->json('data.installments') as $installment) {
            $this->assertEquals('urgent', $installment['urgency']);
        }
    }

    #[Test]
    public function parent_sees_children_installments_with_summary()
    {
        $data = $this->createClassroomWithStudent();
        $parent = $this->createParentWithStudent($data['student']);
        $this->createStudentInstallments($data['student'], $data['fee'], [
            'statuses' => ['overdue', 'pending', 'paid', 'pending'],
            'due_dates' => [
                now()->subDays(6)->format('Y-m-d'),
                now()->addDays(2)->format('Y-m-d'),
                now()->addDays(6)->format('Y-m-d'),
                now()->addDays(19)->format('Y-m-d'),
            ],
            'paid_amounts' => [0, 0, 250, 0],
        ]);

        $response = $this->actingAs($parent, 'api')
            ->getJson('/api/my-installments');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_installments', 4)
            ->assertJsonPath('data.summary.paid_count', 1)
            ->assertJsonPath('data.summary.pending_count', 2)
            ->assertJsonPath('data.summary.overdue_count', 1)
            ->assertJsonPath('data.summary.urgent_count', 2)
            ->assertJsonCount(1, 'data.children')
            ->assertJsonPath('data.children.0.student_id', $data['student']->id)
            ->assertJsonPath('data.children.0.student_name', $data['student']->name)
            ->assertJsonCount(4, 'data.children.0.installments')
            ->assertJsonPath('data.children.0.installments.0.is_overdue', true)
            ->assertJsonPath('data.children.0.installments.0.urgency', 'urgent');
    }
}
