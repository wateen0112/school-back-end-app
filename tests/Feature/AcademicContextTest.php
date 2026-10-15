<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Quizze;
use App\Models\Section;
use App\Models\Specialization;
use App\Models\Subject;
use App\Models\Teacher;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademicContextTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_super_admin' => true]);
    }

    private function createYear(array $overrides = []): AcademicYear
    {
        return AcademicYear::create(array_merge([
            'name' => ['en' => '2026-2027', 'ar' => '2026-2027'],
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'status' => 'draft',
            'is_current' => false,
        ], $overrides));
    }

    #[Test]
    public function activating_a_year_auto_generates_term_1_and_term_2()
    {
        $year = $this->createYear();

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-years/{$year->id}/set-active");

        $response->assertStatus(200)->assertJsonPath('success', true);

        $year->refresh();
        $this->assertEquals('active', $year->status);
        $this->assertTrue((bool) $year->is_current);

        $terms = $year->terms()->orderBy('term_code')->get();
        $this->assertCount(2, $terms);
        $this->assertEquals(['TERM_1', 'TERM_2'], $terms->pluck('term_code')->all());

        // First term becomes the active current term automatically.
        $current = $terms->firstWhere('is_current', true);
        $this->assertNotNull($current);
        $this->assertEquals('TERM_1', $current->term_code);
        $this->assertEquals('active', $current->status);

        // Default ranges split the year and never overlap backwards.
        $this->assertEquals('2026-09-01', $terms[0]->start_date->toDateString());
        $this->assertTrue($terms[1]->start_date->greaterThan($terms[0]->end_date), 'Term 2 must start after Term 1 ends');
    }

    #[Test]
    public function activation_is_atomic_terms_rollback_on_failure()
    {
        $year = $this->createYear();

        // Force the term insert to fail after the year was already switched
        // to active inside the transaction.
        \DB::listen(function ($query) {
            if (str_contains($query->sql, 'insert into `academic_terms`')) {
                throw new \RuntimeException('forced term failure');
            }
        });

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-years/{$year->id}/set-active");

        $response->assertStatus(500);

        $year->refresh();
        $this->assertEquals('draft', $year->status);
        $this->assertFalse((bool) $year->is_current);
        $this->assertCount(0, $year->terms);
    }

    #[Test]
    public function operational_routes_are_locked_without_active_context()
    {
        // No academic year at all → attendance write must be 423.
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/attendance', [])
            ->assertStatus(423)
            ->assertJsonPath('locked', true);

        // Draft year (not active) still locked.
        $this->createYear();
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/attendance', [])
            ->assertStatus(423);
    }

    #[Test]
    public function operational_routes_open_once_context_is_active()
    {
        $year = $this->createYear();
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-years/{$year->id}/set-active")
            ->assertStatus(200);

        // Now passes the guardrail (fails later on payload validation, not 423).
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/attendance', [])
            ->assertStatus(422);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/attendance', [])
            ->assertDontSee('423', false);
    }

    #[Test]
    public function quiz_creation_is_bound_to_the_active_term()
    {
        $year = $this->createYear();
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-years/{$year->id}/set-active")
            ->assertStatus(200);

        $activeTerm = $year->terms()->firstWhere('is_current', true);

        $quiz = $this->postQuiz();
        $quiz->assertStatus(201);

        $created = Quizze::find($quiz->json('data.id'));
        $this->assertEquals($year->id, $created->academic_year_id);
        $this->assertEquals($activeTerm->id, $created->academic_term_id);
    }

    #[Test]
    public function quiz_creation_rejects_a_non_active_term_id()
    {
        $year = $this->createYear();
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-years/{$year->id}/set-active")
            ->assertStatus(200);

        $otherTerm = $year->terms()->firstWhere('term_code', 'TERM_2');

        $response = $this->postQuiz(['academic_term_id' => $otherTerm->id]);
        $response->assertStatus(422);
    }

    #[Test]
    public function quiz_creation_is_rejected_when_no_active_term_exists()
    {
        // Year active but every term completed → context is null.
        $year = $this->createYear(['status' => 'active', 'is_current' => true]);
        $year->terms()->create([
            'name' => ['en' => 'First Term', 'ar' => 'الفصل الأول'],
            'term_code' => 'TERM_1',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-31',
            'status' => 'completed',
            'is_current' => true,
        ]);

        $this->postQuiz()->assertStatus(423);
    }

    #[Test]
    public function academic_context_endpoint_reports_lock_state()
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/academic-context')
            ->assertStatus(200)
            ->assertJsonPath('locked', true);

        $year = $this->createYear();
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-years/{$year->id}/set-active")
            ->assertStatus(200);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/academic-context')
            ->assertStatus(200)
            ->assertJsonPath('locked', false)
            ->assertJsonPath('data.academic_term.term_code', 'TERM_1');
    }

    #[Test]
    public function completed_term_cannot_be_activated()
    {
        $year = $this->createYear();
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-years/{$year->id}/set-active")
            ->assertStatus(200);

        $term = $year->terms()->firstWhere('term_code', 'TERM_2');
        $term->update(['status' => 'completed']);

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/scheduling/academic-terms/{$term->id}/set-current")
            ->assertStatus(422);
    }

    private function postQuiz(array $extra = [])
    {
        $grade = Grade::create(['Name' => 'G1', 'Notes' => '']);
        $classroom = Classroom::create(['Name_Class' => '1-A', 'Grade_id' => $grade->id]);
        $sectionId = \DB::table('sections')->insertGetId([
            'Name_Section' => json_encode(['en' => 'A', 'ar' => 'أ']),
            'Grade_id' => $grade->id,
            'Class_id' => $classroom->id,
            'Status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subject = Subject::create(['name' => ['en' => 'Math', 'ar' => 'رياضيات'], 'Grade_id' => $grade->id, 'Classroom_id' => $classroom->id]);
        $teacher = new Teacher();
        $teacher->Name = ['en' => 'Teacher', 'ar' => 'معلم'];
        $teacher->email = 'teacher'.rand(1000, 9999).'@test.com';
        $teacher->password = bcrypt('password');
        $teacher->Specialization_id = Specialization::create(['Name' => 'Math'])->id;
        $teacher->Gender_id = \App\Models\Gender::create(['Name' => 'Male'])->id;
        $teacher->Joining_Date = '2026-09-01';
        $teacher->Address = 'Addr';
        $teacher->save();

        return $this->actingAs($this->admin, 'api')->postJson('/api/quizzes', array_merge([
            'Name_ar' => 'اختبار تجريبي',
            'subject_id' => $subject->id,
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'section_id' => $sectionId,
            'teacher_id' => $teacher->id,
        ], $extra));
    }
}
