<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeeklyProgramsTable extends Migration
{
    /**
     * Weekly timetable for each section (class) of every grade/stage.
     *
     * day_of_week: 0 (Sunday) .. 6 (Saturday) — compatible with PHP date('w').
     */
    public function up()
    {
        Schema::create('weekly_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');

            $table->unique(['section_id', 'day_of_week', 'start_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('weekly_programs');
    }
}