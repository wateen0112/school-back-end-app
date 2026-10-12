<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->unique()
                ->references('id')->on('students')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->foreignId('morning_route_id')->nullable()
                ->references('id')->on('transportation_routes')->nullOnDelete();
            $table->foreignId('afternoon_route_id')->nullable()
                ->references('id')->on('transportation_routes')->nullOnDelete();

            // Morning (HOME_TO_SCHOOL): pickup is home, drop-off is school.
            $table->string('morning_pickup_address')->nullable();
            $table->decimal('morning_pickup_lat', 10, 7)->nullable();
            $table->decimal('morning_pickup_lng', 10, 7)->nullable();
            $table->time('morning_pickup_time')->nullable();
            $table->string('morning_dropoff_address')->nullable();
            $table->decimal('morning_dropoff_lat', 10, 7)->nullable();
            $table->decimal('morning_dropoff_lng', 10, 7)->nullable();
            $table->time('morning_dropoff_time')->nullable();

            // Afternoon (SCHOOL_TO_HOME): pickup is school, drop-off is home.
            $table->string('afternoon_pickup_address')->nullable();
            $table->decimal('afternoon_pickup_lat', 10, 7)->nullable();
            $table->decimal('afternoon_pickup_lng', 10, 7)->nullable();
            $table->time('afternoon_pickup_time')->nullable();
            $table->string('afternoon_dropoff_address')->nullable();
            $table->decimal('afternoon_dropoff_lat', 10, 7)->nullable();
            $table->decimal('afternoon_dropoff_lng', 10, 7)->nullable();
            $table->time('afternoon_dropoff_time')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_student_profiles');
    }
};
