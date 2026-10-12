<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportation_route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')
                ->references('id')->on('transportation_routes')->cascadeOnDelete();
            $table->foreignId('student_id')
                ->references('id')->on('students')->cascadeOnDelete();
            $table->unsignedSmallInteger('stop_order')->default(1);
            $table->timestamps();

            $table->unique(['route_id', 'student_id']);
            $table->index(['route_id', 'stop_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_route_stops');
    }
};
