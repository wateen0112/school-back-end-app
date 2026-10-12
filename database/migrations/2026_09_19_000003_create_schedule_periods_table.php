<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulePeriodsTable extends Migration
{
    public function up()
    {
        Schema::create('schedule_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_number');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('type')->default('lesson'); // lesson | break
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'period_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_periods');
    }
}