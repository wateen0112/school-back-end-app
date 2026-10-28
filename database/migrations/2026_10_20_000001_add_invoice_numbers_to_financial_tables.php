<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tables that get auto invoice_number + manual reference (paper invoice matching)
        foreach (['fee_invoices', 'payment_students', 'receipt_students', 'processing_fees', 'salary_payments', 'student_discounts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('invoice_number')->nullable()->after('id');
                $t->string('manual_reference_no')->nullable()->after('invoice_number');
            });
        }

        // expenses already has reference_no for the manual/paper number
        Schema::table('expenses', function (Blueprint $t) {
            $t->string('invoice_number')->nullable()->after('id');
        });

        Schema::table('fee_invoices', fn (Blueprint $t) => $t->unique('invoice_number'));
        Schema::table('payment_students', fn (Blueprint $t) => $t->unique('invoice_number'));
        Schema::table('receipt_students', fn (Blueprint $t) => $t->unique('invoice_number'));
        Schema::table('processing_fees', fn (Blueprint $t) => $t->unique('invoice_number'));
        Schema::table('salary_payments', fn (Blueprint $t) => $t->unique('invoice_number'));
        Schema::table('student_discounts', fn (Blueprint $t) => $t->unique('invoice_number'));
        Schema::table('expenses', fn (Blueprint $t) => $t->unique('invoice_number'));
    }

    public function down(): void
    {
        foreach (['fee_invoices', 'payment_students', 'receipt_students', 'processing_fees', 'salary_payments', 'student_discounts', 'expenses'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique(['invoice_number']);
                $t->dropColumn('invoice_number');
            });
        }
        foreach (['fee_invoices', 'payment_students', 'receipt_students', 'processing_fees', 'salary_payments', 'student_discounts'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('manual_reference_no'));
        }
    }
};
