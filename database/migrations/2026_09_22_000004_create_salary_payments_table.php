<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per employee per pay period.
     * employee_type: "teacher" (teachers table) or "staff" (users table).
     */
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->string('employee_type'); // teacher | staff
            $table->unsignedBigInteger('employee_id');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month'); // 1-12
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->string('status')->default('pending'); // pending | paid | cancelled
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_type', 'employee_id', 'period_year', 'period_month'], 'salary_period_unique');
            $table->index(['status']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
