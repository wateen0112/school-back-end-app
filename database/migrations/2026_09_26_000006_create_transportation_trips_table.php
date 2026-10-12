<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // Trips are historical records: when a route/bus/driver is removed
            // (or wiped via school cascade) the trip survives with the
            // reference nulled instead of blocking the delete.
            $table->foreignId('route_id')->nullable()
                ->references('id')->on('transportation_routes')->nullOnDelete();
            $table->foreignId('bus_id')->nullable()
                ->references('id')->on('transportation_buses')->nullOnDelete();
            $table->foreignId('driver_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->string('direction')->default('HOME_TO_SCHOOL');
            $table->date('trip_date');
            $table->time('scheduled_start_time')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('status')->default('SCHEDULED'); // SCHEDULED, READY, IN_PROGRESS, COMPLETED, CANCELLED, EMERGENCY
            $table->timestamp('emergency_at')->nullable();
            $table->text('cancelled_reason')->nullable();
            // Snapshot of relevant transportation_settings at start time.
            $table->json('settings_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'trip_date']);
            $table->index(['status']);
            $table->index(['driver_user_id', 'status']);
            $table->index(['bus_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_trips');
    }
};
