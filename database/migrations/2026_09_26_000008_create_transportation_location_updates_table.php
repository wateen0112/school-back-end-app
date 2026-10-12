<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_location_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')
                ->references('id')->on('transportation_trips')->cascadeOnDelete();
            $table->foreignId('bus_id')
                ->references('id')->on('transportation_buses')->cascadeOnDelete();
            $table->foreignId('driver_user_id')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->float('heading')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['trip_id', 'recorded_at']);
            $table->index(['driver_user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_location_updates');
    }
};
