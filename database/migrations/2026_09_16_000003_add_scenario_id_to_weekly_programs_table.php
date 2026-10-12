<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScenarioIdToWeeklyProgramsTable extends Migration
{
    public function up()
    {
        Schema::table('weekly_programs', function (Blueprint $table) {
            $table->unsignedBigInteger('scenario_id')->nullable()->after('id');
            $table->foreign('scenario_id')->references('id')->on('timetable_scenarios')->onDelete('set null');
            $table->index('scenario_id');
        });
    }

    public function down()
    {
        Schema::table('weekly_programs', function (Blueprint $table) {
            $table->dropForeign(['scenario_id']);
            $table->dropIndex(['scenario_id']);
            $table->dropColumn('scenario_id');
        });
    }
}
