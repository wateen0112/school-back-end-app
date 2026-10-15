<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teacher salary models:
     *  - monthly: classic base salary generated every month (existing behaviour).
     *  - hourly:  teacher has an hour rate; monthly pay = worked hours × rate.
     *  - custom:  a fixed total amount divided into manually-scheduled
     *             (non-fixed) installments listed in teacher_custom_installments.
     *
     * salary_payments gains a `source` discriminator and an optional link to
     * the originating custom installment. The old period unique index is
     * dropped because custom installments may share the same due month.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('salary_type')->default('monthly')->after('salary'); // monthly | hourly | custom
            $table->decimal('hour_rate', 10, 2)->nullable()->after('salary_type');
            $table->decimal('custom_salary_amount', 12, 2)->nullable()->after('hour_rate');
        });

        Schema::create('teacher_custom_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending | paid | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['due_date']);
        });

        Schema::create('teacher_work_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 5, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['teacher_id', 'work_date']);
        });

        Schema::table('salary_payments', function (Blueprint $table) {
            $table->string('source')->default('monthly')->after('status'); // monthly | hours | custom
            $table->foreignId('installment_id')->nullable()
                ->references('id')->on('teacher_custom_installments')->nullOnDelete();
            $table->dropUnique('salary_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropColumn(['source', 'installment_id']);
            $table->unique(['employee_type', 'employee_id', 'period_year', 'period_month'], 'salary_period_unique');
        });

        Schema::dropIfExists('teacher_work_hours');
        Schema::dropIfExists('teacher_custom_installments');

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['salary_type', 'hour_rate', 'custom_salary_amount']);
        });
    }
};
