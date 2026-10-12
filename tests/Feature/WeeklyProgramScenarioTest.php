<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TimetableScenario;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeeklyProgramScenarioTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function makeLookups(): array
    {
        $grade = Grade::create(['Name' => ['ar' => 'الأولى', 'en' => 'First']]);
        $classroom = Classroom::create(['Name_Class' => ['ar' => 'أ', 'en' => 'A'], 'Grade_id' => $grade->id]);
        $section = Section::create([
            'Name_Section' => ['ar' => 'ش', 'en' => 'Sh'],
            'Status' => 1,
            'Grade_id' => $grade->id,
            'Class_id' => $classroom->id,
        ]);
        $subject = Subject::create([
            'Name' => ['ar' => 'رياضيات', 'en' => 'Math'],
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
        ]);

        return compact('grade', 'classroom', 'section', 'subject');
    }

    private function makeScenario(array $lookups): TimetableScenario
    {
        return TimetableScenario::create([
            'name' => 'مسودة الاختبار',
            'grade_id' => $lookups['grade']->id,
            'classroom_id' => $lookups['classroom']->id,
            'section_id' => $lookups['section']->id,
            'version' => 1,
            'status' => 'draft',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function lesson_can_be_added_into_a_scenario_slot()
    {
        $lookups = $this->makeLookups();
        $scenario = $this->makeScenario($lookups);

        $response = $this->actingAs($this->admin(), 'api')->postJson('/api/weekly-programs', [
            'section_id' => $lookups['section']->id,
            'scenario_id' => $scenario->id,
            'day_of_week' => 2,
            'start_time' => '09:00',
            'end_time' => '09:45',
            'subject_id' => $lookups['subject']->id,
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('weekly_programs', [
            'section_id' => $lookups['section']->id,
            'scenario_id' => $scenario->id,
            'day_of_week' => 2,
            'subject_id' => $lookups['subject']->id,
        ]);
    }

    #[Test]
    public function scenario_id_must_exist_when_provided()
    {
        $lookups = $this->makeLookups();

        $this->actingAs($this->admin(), 'api')->postJson('/api/weekly-programs', [
            'section_id' => $lookups['section']->id,
            'scenario_id' => 999999,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'subject_id' => $lookups['subject']->id,
        ])->assertStatus(422);
    }

    #[Test]
    public function lesson_without_scenario_is_still_created()
    {
        $lookups = $this->makeLookups();

        $this->actingAs($this->admin(), 'api')->postJson('/api/weekly-programs', [
            'section_id' => $lookups['section']->id,
            'day_of_week' => 0,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'subject_id' => $lookups['subject']->id,
        ])->assertStatus(201);

        $this->assertDatabaseHas('weekly_programs', [
            'section_id' => $lookups['section']->id,
            'scenario_id' => null,
        ]);
    }
}
