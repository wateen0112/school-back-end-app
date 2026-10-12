<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaxPeriodsPerDayToTeacherPreferencesTable extends Migration
{
    public function up()
    {
        Schema::table('teacher_preferences', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_periods_per_day')->nullable()->after('max_consecutive');
        });
    }

    public function down()
    {
        Schema::table('teacher_preferences', function (Blueprint $table) {
            $table->dropColumn('max_periods_per_day');
        });
    }
}
