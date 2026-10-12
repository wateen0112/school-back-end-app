<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_trip_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('trip_id')
                ->references('id')->on('transportation_trips')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()
                ->references('id')->on('students')->nullOnDelete();
            $table->string('type'); // TRIP_STARTED, BUS_APPROACHING_PICKUP, STUDENT_PICKED_UP, ...
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('meta')->nullable();
            $table->nullableMorphs('actor');
            $table->timestamps();

            $table->index(['trip_id', 'created_at']);
            $table->index(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_trip_events');
    }
};
