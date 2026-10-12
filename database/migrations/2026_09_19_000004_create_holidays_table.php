<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidaysTable extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('name'); // translatable {ar, en}
            $table->date('date');
            $table->string('type')->default('holiday'); // holiday | exam | closure | special
            $table->boolean('affects_schedule')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'date']);
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
}