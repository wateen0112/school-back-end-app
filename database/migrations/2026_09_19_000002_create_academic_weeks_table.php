<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademicWeeksTable extends Migration
{
    public function up()
    {
        Schema::create('academic_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('week_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->unique(['academic_year_id', 'week_number']);
            $table->index(['academic_year_id', 'start_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('academic_weeks');
    }
}