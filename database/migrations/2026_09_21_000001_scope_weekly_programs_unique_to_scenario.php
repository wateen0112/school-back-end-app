<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ScopeWeeklyProgramsUniqueToScenario extends Migration
{
    /**
     * The previous globally-unique index (section_id, day_of_week, start_time)
     * treated every row as part of one live schedule. Timetable scenarios were
     * added later, so two scenarios could never hold the same slot for the same
     * section and duplicating/optimizing a scenario failed with a duplicate-key
     * error. Scope uniqueness per scenario:
     *
     *   - scenario rows (scenario_id NOT NULL): unique per (scenario_id, section_id, day_of_week, start_time)
     *   - live rows (scenario_id NULL): the original single-schedule guarantee is kept
     *     by mapping NULL to key 0 via a stored generated column.
     *
     * The FK on section_id reuses the old unique index, so a dedicated index
     * must exist before that unique index can be dropped (InnoDB error 1553).
     */
    public function up()
    {
        Schema::table('weekly_programs', function (Blueprint $table) {
            $table->index('section_id', 'weekly_programs_section_id_index');
        });

        Schema::table('weekly_programs', function (Blueprint $table) {
            $table->dropUnique('weekly_programs_section_id_day_of_week_start_time_unique');
            $table->unsignedBigInteger('scenario_key')->storedAs('COALESCE(scenario_id, 0)')->after('scenario_id');
            $table->unique(
                ['scenario_key', 'section_id', 'day_of_week', 'start_time'],
                'weekly_programs_schedule_slot_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('weekly_programs', function (Blueprint $table) {
            $table->dropUnique('weekly_programs_schedule_slot_unique');
            $table->dropColumn('scenario_key');
        });

        Schema::table('weekly_programs', function (Blueprint $table) {
            $table->unique(
                ['section_id', 'day_of_week', 'start_time'],
                'weekly_programs_section_id_day_of_week_start_time_unique'
            );
        });

        Schema::table('weekly_programs', function (Blueprint $table) {
            $table->dropIndex('weekly_programs_section_id_index');
        });
    }
}