<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Academic period hierarchy: AcademicYear → AcademicTerm, plus explicit
 * binding of assessments (quizzes / paper exams) to the active context.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->boolean('is_current')->default(false)->after('status');
        });

        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->text('name'); // translatable {ar, en}
            $table->string('term_code'); // TERM_1 | TERM_2
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('pending'); // pending | active | completed
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['academic_year_id', 'term_code'], 'academic_term_unique');
            $table->index(['academic_year_id', 'status']);
            $table->index(['is_current']);
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('teacher_id')
                ->constrained('academic_years')->nullOnDelete();
            $table->foreignId('academic_term_id')->nullable()->after('academic_year_id')
                ->constrained('academic_terms')->nullOnDelete();
            $table->index(['academic_year_id', 'academic_term_id']);
        });

        Schema::table('paper_exams', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->after('id')
                ->constrained('academic_years')->nullOnDelete();
            $table->foreignId('academic_term_id')->nullable()->after('academic_year_id')
                ->constrained('academic_terms')->nullOnDelete();
            $table->index(['academic_year_id', 'academic_term_id']);
        });
    }

    public function down(): void
    {
        Schema::table('paper_exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_term_id');
            $table->dropConstrainedForeignId('academic_year_id');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_term_id');
            $table->dropConstrainedForeignId('academic_year_id');
        });

        Schema::dropIfExists('academic_terms');

        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn('is_current');
        });
    }
};
