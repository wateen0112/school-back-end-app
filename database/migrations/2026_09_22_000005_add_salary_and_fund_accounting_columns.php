<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounting module foundation:
     *  - base monthly salary on teachers and staff (users)
     *  - expense / salary_payment links in the fund ledger
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->decimal('salary', 10, 2)->nullable()->after('Address');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('salary', 10, 2)->nullable()->after('email');
        });

        Schema::table('fund_accounts', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->after('payment_id')
                ->references('id')->on('expenses')->nullOnDelete();
            $table->foreignId('salary_payment_id')->nullable()->after('expense_id')
                ->references('id')->on('salary_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fund_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
            $table->dropConstrainedForeignId('salary_payment_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('salary');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('salary');
        });
    }
};
