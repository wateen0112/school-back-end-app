<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()
                ->references('id')->on('expense_categories')->nullOnDelete();
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['expense_date']);
            $table->index(['category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
