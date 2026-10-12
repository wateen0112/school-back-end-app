<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily usage metrics (students, teachers, parents, storage, api, ai...).
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('usage_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('metric', 50);
            $table->unsignedBigInteger('value')->default(0);
            $table->date('recorded_on');
            $table->timestamps();

            $table->unique(['school_id', 'metric', 'recorded_on']);
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_metrics');
    }
};