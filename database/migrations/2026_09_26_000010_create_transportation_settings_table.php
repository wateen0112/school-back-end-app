<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->cascadeOnDelete();
            $table->unsignedSmallInteger('geofence_radius_m')->default(150);
            $table->unsignedSmallInteger('gps_interval_s')->default(10);
            $table->boolean('parent_live_tracking')->default(true);
            $table->boolean('approaching_notifications')->default(true);
            $table->boolean('notify_parents_on_emergency')->default(true);
            $table->time('morning_default_start')->nullable();
            $table->time('afternoon_default_start')->nullable();
            $table->json('working_days')->nullable(); // [1..7], null = all weekdays
            $table->string('map_provider')->default('osm'); // osm | google | mapbox (future)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_settings');
    }
};
