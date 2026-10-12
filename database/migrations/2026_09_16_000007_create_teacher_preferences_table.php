<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeacherPreferencesTable extends Migration
{
    public function up()
    {
        Schema::create('teacher_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade')->unique();
            $table->unsignedTinyInteger('preferred_consecutive')->nullable();
            $table->unsignedTinyInteger('max_consecutive')->nullable();
            $table->time('preferred_start_time')->nullable();
            $table->time('preferred_end_time')->nullable();
            $table->json('avoid_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('teacher_preferences');
    }
}