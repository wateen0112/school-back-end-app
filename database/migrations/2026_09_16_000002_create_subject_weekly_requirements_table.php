<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubjectWeeklyRequirementsTable extends Migration
{
    public function up()
    {
        Schema::create('subject_weekly_requirements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('grade_id');
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->unsignedInteger('periods_per_week')->default(1);
            $table->unsignedInteger('min_periods_per_day')->default(0);
            $table->unsignedInteger('max_periods_per_day')->default(2);
            $table->boolean('allow_consecutive')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('grade_id')->references('id')->on('grades')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->unique(['subject_id', 'grade_id', 'classroom_id'], 'subject_grade_classroom_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subject_weekly_requirements');
    }
}
