<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('type', 40); // cash | siblings | excellence | teacher_parent | special
            $table->decimal('value', 10, 2);
            $table->boolean('is_percentage')->default(false);
            $table->string('reason')->nullable();
            $table->string('year')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('student_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_discounts');
    }
}
