<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // A driver is an existing system user (App\User) holding the "driver" role.
            $table->foreignId('user_id')->unique()
                ->references('id')->on('users')->cascadeOnDelete();
            $table->string('employee_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('license_no')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_driver_profiles');
    }
};
