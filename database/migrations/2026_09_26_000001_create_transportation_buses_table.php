<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_buses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()
                ->references('id')->on('school_branches')->nullOnDelete();
            $table->string('bus_number');
            $table->string('name')->nullable();
            $table->string('license_plate')->nullable();
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->string('status')->default('AVAILABLE'); // AVAILABLE, ASSIGNED, ON_TRIP, OFFLINE, MAINTENANCE, INACTIVE
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->unique(['school_id', 'bus_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_buses');
    }
};
