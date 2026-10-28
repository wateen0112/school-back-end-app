<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYearsToStudentDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_discounts', function (Blueprint $table) {
            // JSON array of academic-year labels; NULL applies to all years.
            $table->json('years')->nullable()->after('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_discounts', function (Blueprint $table) {
            $table->dropColumn('years');
        });
    }
}
