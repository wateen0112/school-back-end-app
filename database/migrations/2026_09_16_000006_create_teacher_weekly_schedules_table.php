<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeacherWeeklySchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('teacher_weekly_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday ... 6 = Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_preferred')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'day_of_week']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('teacher_weekly_schedules');
    }
}