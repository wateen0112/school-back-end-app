<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalPeriodsPerYearToSubjectWeeklyRequirementsTable extends Migration
{
    public function up()
    {
        Schema::table('subject_weekly_requirements', function (Blueprint $table) {
            // Total number of lessons planned for this subject across the whole
            // academic year. Used to cross-check periods_per_week against the
            // implied weekly average (total / teaching weeks).
            $table->unsignedInteger('total_periods_per_year')->nullable()->after('periods_per_week');
        });
    }

    public function down()
    {
        Schema::table('subject_weekly_requirements', function (Blueprint $table) {
            $table->dropColumn('total_periods_per_year');
        });
    }
}
