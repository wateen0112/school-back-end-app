<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('entity', 50)->index(); // students,teachers,my__parents,classrooms,subjects,fees,attendances
            $table->string('file_name', 255);
            $table->string('original_name', 255)->nullable();
            $table->string('status', 30)->default('pending')->index(); // pending/validating/importing/completed/failed
            $table->unsignedSmallInteger('total_rows')->default(0);
            $table->unsignedSmallInteger('imported_rows')->default(0);
            $table->unsignedSmallInteger('skipped_rows')->default(0);
            $table->json('errors')->nullable(); // per-row error list
            $table->json('report')->nullable(); // summary report
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
