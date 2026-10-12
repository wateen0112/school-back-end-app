<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscription plans offered by the platform.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->string('billing_cycle', 10)->default('monthly');
            $table->json('features')->nullable();
            $table->unsignedInteger('max_students')->nullable();
            $table->unsignedInteger('max_teachers')->nullable();
            $table->unsignedInteger('max_branches')->default(1);
            $table->unsignedInteger('max_storage_gb')->default(5);
            $table->unsignedBigInteger('max_api_calls_monthly')->default(100000);
            $table->boolean('ai_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};