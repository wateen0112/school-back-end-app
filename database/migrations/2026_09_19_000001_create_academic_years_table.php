<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademicYearsTable extends Migration
{
    public function up()
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->text('name'); // translatable {ar, en}
            $table->date('start_date');
            $table->date('end_date');
            $table->json('working_days')->nullable(); // [0..6], 0 = Sunday (matches date('w'))
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('period_duration_min')->default(45);
            $table->unsignedSmallInteger('periods_count')->default(8);
            $table->json('breaks')->nullable(); // [{start, end, name}]
            $table->string('status')->default('draft'); // draft | active | archived
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('academic_years');
    }
}