<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStudentInstallmentIdToStudentAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_accounts', function (Blueprint $table) {
            $table->foreignId('student_installment_id')->nullable()->after('payment_id')->references('id')->on('student_installments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_accounts', function (Blueprint $table) {
            $table->dropForeign(['student_installment_id']);
            $table->dropColumn('student_installment_id');
        });
    }
}
