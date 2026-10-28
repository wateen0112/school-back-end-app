<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Grade;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Religion;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentMonthlyReport;
use App\Models\StudentRewardPenalty;
use App\Models\Type_Blood;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RewardPenaltyApiTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => ['en' => 'Test School', 'ar' => 'مدرسة تجريبية'],
            'email' => 'school@test.local',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        $this->student = $this->makeStudent($this->school->id);
    }

    private function makeStudent(int $schoolId): Student
    {
        $gender = Gender::create(['Name' => 'Male']);
        $nationality = Nationalitie::create(['Name' => 'Syrian']);
        $blood = Type_Blood::create(['Name' => 'O+']);
        $grade = Grade::create(['Name' => 'First Grade', 'Notes' => '']);
        $classroom = Classroom::create(['Name_Class' => '1-A', 'Grade_id' => $grade->id]);
        $parent = My_Parent::create([
            'email' => 'parent'.rand(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'Name_Father' => 'Father',
            'National_ID_Father' => '1',
            'Phone_Father' => '1',
            'Job_Father' => '1',
            'Passport_ID_Father' => '1',
            'Nationality_Father_id' => $nationality->id,
            'Blood_Type_Father_id' => $blood->id,
            'Religion_Father_id' => Religion::create(['Name' => 'Islam'])->id,
            'Address_Father' => 'addr',
        ]);

        return Student::create([
            'name' => ['en' => 'Test Student', 'ar' => 'طالب تجريبي'],
            'email' => 'student'.rand(1000, 9999).'@test.local',
            'password' => bcrypt('password'),
            'gender_id' => $gender->id,
            'nationalitie_id' => $nationality->id,
            'blood_id' => $blood->id,
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'section_id' => Section::create([
                'Name_Section' => json_encode(['en' => 'A', 'ar' => 'أ']),
                'Grade_id' => $grade->id,
                'Class_id' => $classroom->id,
                'Status' => 1,
            ])->id,
            'parent_id' => $parent->id,
            'Date_Birth' => '2015-01-01',
            'academic_year' => '2026-2027',
            'school_id' => $schoolId,
        ]);
    }

    #[Test]
    public function it_creates_lists_updates_and_deletes_a_manual_reward(): void
    {
        $create = $this->actingAs($this->admin, 'api')->postJson('/api/rewards-penalties', [
            'student_id' => $this->student->id,
            'type' => 'reward',
            'title' => 'شهادة تقدير',
            'points' => 20,
        ]);
        $create->assertStatus(201)->assertJsonPath('data.type', 'reward');
        $id = $create->json('data.id');

        $this->assertDatabaseHas('student_reward_penalties', [
            'id' => $id,
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'source' => 'manual',
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/rewards-penalties')
            ->assertOk()
            ->assertJsonPath('data.total', 1);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/rewards-penalties/{$id}", ['points' => 30])
            ->assertOk()
            ->assertJsonPath('data.points', 30);

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/rewards-penalties/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('student_reward_penalties', ['id' => $id]);
    }

    #[Test]
    public function it_rejects_an_invalid_type(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/rewards-penalties', [
            'student_id' => $this->student->id,
            'type' => 'bonus',
            'title' => 'x',
        ])->assertStatus(422);
    }

    #[Test]
    public function it_returns_stats_scoped_to_the_school(): void
    {
        StudentRewardPenalty::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'type' => 'reward',
            'source' => 'manual',
            'title' => 'R',
            'points' => 10,
            'status' => 'approved',
        ]);
        StudentRewardPenalty::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'type' => 'penalty',
            'source' => 'manual',
            'title' => 'P',
            'points' => 5,
            'status' => 'approved',
        ]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/rewards-penalties/stats')
            ->assertOk()
            ->assertJsonPath('data.rewards_count', 1)
            ->assertJsonPath('data.penalties_count', 1)
            ->assertJsonPath('data.total_reward_points', 10)
            ->assertJsonPath('data.total_penalty_points', 5);
    }

    #[Test]
    public function it_suggests_rewards_and_penalties_from_progress_and_applies_them(): void
    {
        $other = $this->makeStudent($this->school->id);
        $other->update(['Grade_id' => $this->student->Grade_id]);

        StudentMonthlyReport::create([
            'student_id' => $this->student->id,
            'month' => 3,
            'year' => 2026,
            'progress_score' => 92,
            'trend' => 'improved',
        ]);
        StudentMonthlyReport::create([
            'student_id' => $other->id,
            'month' => 3,
            'year' => 2026,
            'progress_score' => 40,
            'trend' => 'declined',
        ]);

        $suggestions = $this->actingAs($this->admin, 'api')
            ->getJson('/api/rewards-penalties/suggestions')
            ->assertOk()
            ->json('data.suggestions');

        $this->assertCount(2, $suggestions);
        $types = collect($suggestions)->pluck('type')->sort()->values()->all();
        $this->assertSame(['penalty', 'reward'], $types);

        $apply = $this->actingAs($this->admin, 'api')->postJson('/api/rewards-penalties/apply-suggestions', [
            'items' => $suggestions,
        ]);
        $apply->assertOk()->assertJsonPath('data.created', 2);

        $this->assertDatabaseCount('student_reward_penalties', 2);

        // Already applied → no repeat suggestions for the same period.
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/rewards-penalties/suggestions')
            ->assertOk()
            ->assertJsonPath('data.suggestions', []);
    }

    #[Test]
    public function it_reads_and_updates_settings(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/rewards-penalties/settings')
            ->assertOk()
            ->assertJsonPath('data.rewards_enabled', true);

        $this->actingAs($this->admin, 'api')
            ->putJson('/api/rewards-penalties/settings', [
                'reward_threshold' => 90,
                'penalties_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.reward_threshold', 90)
            ->assertJsonPath('data.penalties_enabled', false);
    }

    #[Test]
    public function records_are_isolated_across_schools(): void
    {
        $otherSchool = School::create([
            'name' => ['en' => 'Other', 'ar' => 'أخرى'],
            'email' => 'other@test.local',
        ]);
        $otherAdmin = User::factory()->create(['school_id' => $otherSchool->id]);

        StudentRewardPenalty::create([
            'student_id' => $this->student->id,
            'school_id' => $this->school->id,
            'type' => 'reward',
            'source' => 'manual',
            'title' => 'R',
            'points' => 10,
            'status' => 'approved',
        ]);

        $this->actingAs($otherAdmin, 'api')
            ->getJson('/api/rewards-penalties')
            ->assertOk()
            ->assertJsonPath('data.total', 0);
    }
}
