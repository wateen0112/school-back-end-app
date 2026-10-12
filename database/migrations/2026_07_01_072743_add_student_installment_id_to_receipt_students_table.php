<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStudentInstallmentIdToReceiptStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipt_students', function (Blueprint $table) {
            $table->foreignId('student_installment_id')->nullable()->after('student_id')->references('id')->on('student_installments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipt_students', function (Blueprint $table) {
            $table->dropForeign(['student_installment_id']);
            $table->dropColumn('student_installment_id');
        });
    }
}
