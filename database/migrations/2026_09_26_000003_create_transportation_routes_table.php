<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()
                ->references('id')->on('school_branches')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('direction')->default('HOME_TO_SCHOOL'); // HOME_TO_SCHOOL, SCHOOL_TO_HOME
            $table->foreignId('bus_id')->nullable()
                ->references('id')->on('transportation_buses')->nullOnDelete();
            $table->foreignId('driver_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->time('scheduled_start_time')->nullable();
            $table->json('active_days')->nullable(); // [1..7], null = every school day
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'direction']);
            $table->unique(['school_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_routes');
    }
};
