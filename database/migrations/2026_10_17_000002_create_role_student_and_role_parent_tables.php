<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'student_id']);
        });

        Schema::create('role_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('my__parents')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_parent');
        Schema::dropIfExists('role_student');
    }
};
