<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolScheduleSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('school_schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade')->unique();
            $table->json('days')->nullable(); // working days, e.g. [0,1,2,3,4]
            $table->time('start_time')->nullable(); // school day starts
            $table->time('end_time')->nullable(); // school day ends
            $table->json('breaks')->nullable(); // [{start,end,name}]
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('school_schedule_settings');
    }
}