<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchedulePeriod;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchedulePeriodsDayTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_super_admin' => true]);
    }

    private function createYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => ['en' => '2026-2027', 'ar' => '2026-2027'],
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'working_days' => [0, 1, 2, 3, 4],
        ]);
    }

    #[Test]
    public function periods_can_be_scoped_to_a_specific_day()
    {
        $year = $this->createYear();

        $response = $this->actingAs($this->admin, 'api')->putJson(
            "/api/scheduling/schedule-periods/{$year->id}",
            [
                'periods' => [
                    ['period_number' => 1, 'start_time' => '08:00', 'end_time' => '08:45', 'type' => 'lesson'],
                    ['period_number' => 2, 'start_time' => '08:45', 'end_time' => '09:30', 'type' => 'lesson'],
                    // Tuesday override for period 2
                    ['period_number' => 2, 'day_of_week' => 2, 'start_time' => '10:30', 'end_time' => '11:15', 'type' => 'lesson'],
                ],
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('schedule_periods', [
            'academic_year_id' => $year->id, 'period_number' => 2, 'day_of_week' => 2,
        ]);
    }

    #[Test]
    public function day_specific_period_overrides_the_global_one()
    {
        $year = $this->createYear();

        $this->actingAs($this->admin, 'api')->putJson(
            "/api/scheduling/schedule-periods/{$year->id}",
            [
                'periods' => [
                    ['period_number' => 1, 'start_time' => '08:00', 'end_time' => '08:45', 'type' => 'lesson'],
                    ['period_number' => 2, 'start_time' => '08:45', 'end_time' => '09:30', 'type' => 'lesson'],
                    ['period_number' => 2, 'day_of_week' => 2, 'start_time' => '10:30', 'end_time' => '11:15', 'type' => 'lesson'],
                ],
            ]
        );

        $tuesday = SchedulePeriod::forDay($year->id, 2);
        $monday = SchedulePeriod::forDay($year->id, 1);

        $tueP2 = $tuesday->firstWhere('period_number', 2);
        $monP2 = $monday->firstWhere('period_number', 2);

        $this->assertEquals('10:30', substr($tueP2->start_time, 0, 5));
        $this->assertEquals(2, $tueP2->day_of_week);
        $this->assertEquals('08:45', substr($monP2->start_time, 0, 5));
        $this->assertNull($monP2->day_of_week);
    }

    #[Test]
    public function duplicate_period_number_on_the_same_day_is_rejected()
    {
        $year = $this->createYear();

        $response = $this->actingAs($this->admin, 'api')->putJson(
            "/api/scheduling/schedule-periods/{$year->id}",
            [
                'periods' => [
                    ['period_number' => 1, 'day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '08:45', 'type' => 'lesson'],
                    ['period_number' => 1, 'day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '09:45', 'type' => 'lesson'],
                ],
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['periods.1.period_number']);
    }

    #[Test]
    public function resyncing_periods_preserves_day_overrides()
    {
        $year = $this->createYear();

        $this->actingAs($this->admin, 'api')->putJson(
            "/api/scheduling/schedule-periods/{$year->id}",
            [
                'periods' => [
                    ['period_number' => 1, 'start_time' => '08:00', 'end_time' => '08:45', 'type' => 'lesson'],
                    ['period_number' => 1, 'day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '09:45', 'type' => 'lesson'],
                ],
            ]
        );

        // Year config change triggers a global resync.
        $year->update(['period_duration_min' => 40, 'periods_count' => 8]);
        (new \App\Services\Scheduling\AnnualCalendarService())->syncPeriods($year->refresh());

        $this->assertDatabaseHas('schedule_periods', [
            'academic_year_id' => $year->id, 'day_of_week' => 3, 'start_time' => '09:00:00',
        ]);
    }
}
