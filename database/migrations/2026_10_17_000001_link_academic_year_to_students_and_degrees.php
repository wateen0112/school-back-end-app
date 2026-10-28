<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Binds every student and every mark to an academic year.
 *
 * - `students.academic_year_id` — a real link (the legacy free-text
 *   `academic_year` label is kept and re-synced by the controller).
 * - `degrees.academic_year_id` — marks inherit the year of the quiz they
 *   belong to, so results stay attributable to the year they were earned in.
 *
 * Both columns are nullable at the database level so a brand-new install with
 * no years yet can migrate; the API/form layer enforces the link.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'academic_year_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('academic_year_id')->nullable()->after('academic_year')
                    ->constrained('academic_years')->nullOnDelete();
                $table->index('academic_year_id');
            });
        }

        if (! Schema::hasColumn('degrees', 'academic_year_id')) {
            Schema::table('degrees', function (Blueprint $table) {
                $table->foreignId('academic_year_id')->nullable()->after('student_id')
                    ->constrained('academic_years')->nullOnDelete();
                $table->index('academic_year_id');
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
        });

        Schema::table('degrees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
        });
    }

    private function backfill(): void
    {
        // Marks inherit the year from their quiz.
        DB::statement('
            UPDATE degrees d
            JOIN quizzes q ON q.id = d.quizze_id
            SET d.academic_year_id = q.academic_year_id
            WHERE d.academic_year_id IS NULL AND q.academic_year_id IS NOT NULL
        ');

        // Students whose stored label matches a year name (en or ar).
        DB::statement("
            UPDATE students s
            JOIN academic_years y
              ON y.school_id = s.school_id
             AND (
                   JSON_UNQUOTE(JSON_EXTRACT(y.name, '$.en')) = s.academic_year
                OR JSON_UNQUOTE(JSON_EXTRACT(y.name, '$.ar')) = s.academic_year
             )
            SET s.academic_year_id = y.id
            WHERE s.academic_year_id IS NULL
        ");

        // Fall back to the school's current year for any student still unlinked.
        DB::statement('
            UPDATE students s
            JOIN academic_years y ON y.school_id = s.school_id AND y.is_current = 1
            SET s.academic_year_id = y.id
            WHERE s.academic_year_id IS NULL
        ');

        // Students without a school still need a year: use the global current one.
        DB::statement('
            UPDATE students
            SET academic_year_id = (
                SELECT id FROM academic_years WHERE is_current = 1 ORDER BY id LIMIT 1
            )
            WHERE academic_year_id IS NULL
              AND EXISTS (SELECT 1 FROM academic_years WHERE is_current = 1)
        ');

        // Orphan marks fall back to the current year.
        DB::statement('
            UPDATE degrees
            SET academic_year_id = (
                SELECT id FROM academic_years WHERE is_current = 1 ORDER BY id LIMIT 1
            )
            WHERE academic_year_id IS NULL
              AND EXISTS (SELECT 1 FROM academic_years WHERE is_current = 1)
        ');

        // Quizzes predating the academic-year column fall back to the current year.
        DB::statement('
            UPDATE quizzes
            SET academic_year_id = (
                SELECT id FROM academic_years WHERE is_current = 1 ORDER BY id LIMIT 1
            )
            WHERE academic_year_id IS NULL
              AND EXISTS (SELECT 1 FROM academic_years WHERE is_current = 1)
        ');
    }
};
