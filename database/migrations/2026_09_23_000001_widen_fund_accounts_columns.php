<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expenses and salary payments use decimal(10,2) amounts, while the legacy
     * fund ledger columns could only hold decimal(8,2). Widen so payouts
     * above 999,999.99 no longer overflow the ledger insert.
     */
    public function up(): void
    {
        Schema::table('fund_accounts', function (Blueprint $table) {
            $table->decimal('Debit', 12, 2)->nullable()->change();
            $table->decimal('credit', 12, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fund_accounts', function (Blueprint $table) {
            $table->decimal('Debit', 8, 2)->nullable()->change();
            $table->decimal('credit', 8, 2)->nullable()->change();
        });
    }
};