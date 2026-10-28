<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_reward_penalties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->enum('type', ['reward', 'penalty']);
            $table->enum('source', ['manual', 'auto'])->default('manual');
            $table->string('title');
            $table->text('reason')->nullable();
            $table->decimal('points', 8, 2)->default(0);
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('progress_score_snapshot', 5, 2)->nullable();
            $table->string('period_month', 7)->nullable();
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('approved');
            $table->unsignedTinyInteger('severity')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'type']);
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'status']);
        });

        Schema::create('reward_penalty_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id')->nullable()->unique();
            $table->boolean('rewards_enabled')->default(true);
            $table->boolean('penalties_enabled')->default(true);
            $table->boolean('auto_apply')->default(false);
            $table->decimal('reward_threshold', 5, 2)->default(85);
            $table->decimal('penalty_threshold', 5, 2)->default(50);
            $table->decimal('improved_bonus_points', 8, 2)->default(10);
            $table->decimal('declined_penalty_points', 8, 2)->default(10);
            $table->json('reward_catalog')->nullable();
            $table->json('penalty_catalog')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_penalty_settings');
        Schema::dropIfExists('student_reward_penalties');
    }
};
