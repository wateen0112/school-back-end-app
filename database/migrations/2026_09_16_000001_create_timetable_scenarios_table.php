<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimetableScenariosTable extends Migration
{
    public function up()
    {
        Schema::create('timetable_scenarios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('grade_id')->nullable();
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->integer('version')->default(1);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_active')->default(false);
            $table->json('locked_slots')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('grade_id')->references('id')->on('grades')->onDelete('set null');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('set null');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
            $table->index(['grade_id', 'status']);
            $table->index(['section_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('timetable_scenarios');
    }
}
