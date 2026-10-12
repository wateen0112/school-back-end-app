<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentInstallmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->references('id')->on('installment_plans')->onDelete('cascade');
            $table->foreignId('installment_id')->references('id')->on('installments')->onDelete('cascade');
            $table->foreignId('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreignId('fee_invoice_id')->nullable()->references('id')->on('fee_invoices')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_amount', 10, 2);
            $table->string('status')->default('pending');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('overdue_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_installments');
    }
}
