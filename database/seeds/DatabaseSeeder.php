<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->disableForeignKeys();
        $this->flushRelatedRows();
        $this->enableForeignKeys();

        // Lookup tables
        $this->call(GenderTableSeeder::class);
        $this->call(BloodTableSeeder::class);
        $this->call(NationalitiesTableSeeder::class);
        $this->call(religionTableSeeder::class);
        $this->call(SpecializationsTableSeeder::class);

        // Users
        $this->call(UserSeeder::class);

        // Platform layer (plans, subscription, settings, security, announcements)
        $this->call(PlatformTableSeeder::class);

        // School structure
        $this->call(GradeSeeder::class);
        $this->call(ClassroomTableSeeder::class);
        $this->call(SectionsTableSeeder::class);

        // Teachers & subjects
        $this->call(TeachersTableSeeder::class);
        $this->call(SubjectsTableSeeder::class);

        // Parents & students
        $this->call(ParentsTableSeeder::class);
        $this->call(StudentsTableSeeder::class);

        // Second child for the demo parent + weekly timetable for his children
        $this->call(ParentChildrenTimetableSeeder::class);

        // Extra teachers + subjects for one classroom/section (full-week timetable test)
        $this->call(WeeklyTimetableTestSeeder::class);

        // Two full exam classes with five single-gender sections each, their curriculum,
        // teachers and students
        $this->call(\Database\Seeders\DemoSecondarySectionsSeeder::class);

        // Rebuild the weekly timetable and its scenarios last, once every section,
        // subject and teacher exists. Runs here because it clears weekly_programs
        // wholesale, which would otherwise discard the grid seeded above.
        $this->call(\Database\Seeders\DemoWeeklyTimetableSeeder::class);

        // Fees & installments
        $this->call(FeesTableSeeder::class);
        $this->call(InstallmentPlansTableSeeder::class);

        // Academic records
        $this->call(EvaluationsTableSeeder::class);
        $this->call(AttendanceTableSeeder::class);

        // Quizzes & questions
        $this->call(QuizSeeder::class);

        // Completed exams for the demo student (student1@school.local)
        $this->call(StudentExamsSeeder::class);

        // Published paper exams with marks for every current student
        $this->call(PaperExamMarksSeeder::class);

        // Settings
        $this->call(SettingsTableSeeder::class);

        // Last: the roles and staff accounts of the demo school, plus a couple of live
        // approval requests. Runs at the end because the sample requests point at real
        // teachers and students, which the seeders above are what create.
        $this->call(\Database\Seeders\DemoSchoolStaffSeeder::class);
    }

    /**
     * Disable foreign key checks so rows can be flushed in any order.
     */
    protected function disableForeignKeys(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    /**
     * Re-enable foreign key checks.
     */
    protected function enableForeignKeys(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Drop FK-linked rows so seeders can empty their tables safely
     * on a non-fresh database (e.g. second run of db:seed).
     */
    protected function flushRelatedRows(): void
    {
        $tables = [
            'fund_accounts',
            'salary_payments',
            'pending_requests',
            'expenses',
            'expense_categories',
            'attendances',
            'evaluations',
            'student_installments',
            'installments',
            'installment_plans',
            'student_accounts',
            'fee_invoices',
            'fees',
            'subjects',
            'teacher_section',
            'students',
            'teachers',
            'sections',
            'Classrooms',
            'Grades',
            'my__parents',
            'users',
            'role_user',
            'roles',
            'subscriptions',
            'announcements',
            'integrations',
            'permission_templates',
            'security_policies',
            'usage_metrics',
            'audit_logs',
            'platform_settings',
            'plans',
            'school_branches',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }
}
