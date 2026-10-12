<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // Which sensitive action is waiting: pay_salary, send_marks,
            // send_report, update_teacher, ... resolved by PendingActionRegistry.
            $table->string('action', 60);

            // What the action targets (SalaryPayment #12, Teacher #5, ...).
            // Kept as a loose (type, id) pair so one table can gate any action.
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            // Bilingual title/description snapshot so the queue stays readable
            // even if the action label changes later.
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();

            // The original request body, replayed verbatim when an admin approves.
            $table->json('payload')->nullable();

            // Why the requester needs this done.
            $table->text('reason')->nullable();

            $table->string('status', 20)->default('pending');   // pending, approved, rejected, cancelled
            $table->string('priority', 20)->default('normal');  // normal, high, urgent

            // Who asked for it. Polymorphic because teachers, staff and even
            // parents can end up requesting an approval.
            $table->unsignedBigInteger('requested_by_id');
            $table->string('requested_by_type', 191);

            // Who decided, and when. Null while the request is still pending.
            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->string('reviewed_by_type', 191)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Set once the approved action actually executed. A failure keeps
            // the row approved with `applied_at` null plus the error below.
            $table->timestamp('applied_at')->nullable();
            $table->text('apply_error')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['requested_by_id', 'requested_by_type']);
            $table->index(['action', 'status']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_requests');
    }
};