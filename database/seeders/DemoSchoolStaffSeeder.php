<?php

namespace Database\Seeders;

use App\Models\PendingRequest;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestService;
use App\Services\Roles\PermissionRegistry;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Staff accounts with separated roles / permissions for the demo school, built so the
 * sensitive-action approval queue can be exercised end to end in the browser.
 *
 * The queue only engages for somebody who *lacks* the action's approval permission, so the
 * roster deliberately pairs a "module owner" (holds `<module>.manage`, therefore queues the
 * request) with a "reviewer" (holds `<module>.approve`, therefore approves it):
 *
 *   | account                 | role                    | can queue            | can approve        |
 *   |-------------------------|-------------------------|----------------------|--------------------|
 *   | admin@school.local      | School Administrator    | no (executes direct) | everything         |
 *   | finance@school.local    | Finance Officer         | pay_salary           | -                  |
 *   | cfo@school.local        | Finance Director        | -                    | pay_salary         |
 *   | academic@school.local   | Academic Coordinator    | send_marks           | -                  |
 *   | dean@school.local       | Dean of Studies         | -                    | send_marks         |
 *   | reports@school.local    | Student Reports Officer | send_report          | -                  |
 *   | supervisor@school.local | Reports Supervisor      | -                    | send_report        |
 *   | hr@school.local         | HR Manager              | update_teacher       | -                  |
 *   | registrar@school.local  | School Registrar        | -                    | update_teacher     |
 *   | principal@school.local  | Principal               | no (executes direct) | everything         |
 *   | transport@school.local  | Transport Manager       | -                    | -                  |
 *   | viewer@school.local     | Read-only Observer      | -                    | -                  |
 *
 * Suggested walkthrough: log in as finance@school.local, pay a salary (the API answers 202
 * and nothing moves), then log in as cfo@school.local and approve it from /pending-requests.
 *
 * `transport@school.local` and `viewer@school.local` exist to prove scoping: they cannot see
 * or touch the other modules, and neither can be handed a request to review.
 *
 * Every account uses the password "password123", matching the other demo seeders.
 *
 * The seeder is idempotent: users are matched by e-mail, roles by their English name, so
 * re-running updates in place and never duplicates.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\DemoSchoolStaffSeeder"
 *   php artisan db:seed --class="Database\Seeders\DemoSchoolStaffSeeder" --school=1
 *
 * From another seeder:
 *   $this->callWith([DemoSchoolStaffSeeder::class], ['school' => 1, 'pending' => false]);
 *
 * `pending` (default true) also drops a couple of live requests into the queue so the
 * dashboard badge and the reviewer screen are not empty on first login. Pass false to skip.
 */
class DemoSchoolStaffSeeder extends Seeder
{
    /** Password given to every seeded account. */
    public const PASSWORD = 'password123';

    /** The demo school is looked up by this address (see database/seeds/UserSeeder.php). */
    private const SCHOOL_EMAIL = 'admin@school.local';

    /** Every account needs these two to reach the dashboard and read its own inbox. */
    private const BASE = ['dashboard.view', 'my_notifications.manage'];

    public function run(?string $school = null, ?bool $pending = null): void
    {
        $school = $this->resolveSchool($school);
        $withPending = $pending ?? ! $this->flag('without-pending');

        $roles = $this->seedRoles($school);
        $users = $this->seedUsers($school, $roles);

        if ($withPending) {
            $this->seedSampleRequests($school, $users);
        }

        $this->report($school, $users, $withPending);
    }

    // ------------------------------------------------------------------
    // Roles
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{en: string, ar: string, description: string, permissions: array<string, bool>}>
     */
    private function roleDefinitions(): array
    {
        return [
            'administrator' => [
                'en'          => 'School Administrator',
                'ar'          => 'مدير المدرسة',
                'description' => 'Every permission the platform knows about: executes sensitive actions directly and reviews anything left in the queue.',
                'permissions' => $this->everyPermission(),
            ],

            // ---- requesters: hold <module>.manage, never <module>.approve ----
            'finance_officer' => [
                'en'          => 'Finance Officer',
                'ar'          => 'مسؤول المالية',
                'description' => 'Runs the finance day to day. Paying a salary is sensitive, so it is queued for the Finance Director.',
                'permissions' => $this->perms([
                    'salaries.manage',
                    'fees.manage', 'fee_invoices.manage', 'receipts.manage', 'payments.manage',
                    'processing_fee.manage', 'installment_plans.manage', 'student_installments.manage',
                    'expenses.manage', 'financial_reports.manage', 'accounting_reports.manage',
                ]),
            ],

            'academic_coordinator' => [
                'en'          => 'Academic Coordinator',
                'ar'          => 'منسق الجانب الأكاديمي',
                'description' => 'Owns the academic year, subjects and assessments. Publishing marks to parents is queued for the Dean of Studies.',
                'permissions' => $this->perms([
                    'grades.manage', 'classrooms.manage', 'sections.manage', 'subjects.manage',
                    'specializations.manage', 'weekly_programs.manage', 'timetable_scenarios.manage',
                    'scheduling.view', 'academic_years.manage', 'holidays.manage',
                    'quizzes.manage', 'questions.manage', 'paper_exams.manage', 'students.manage',
                ]),
            ],

            'reports_officer' => [
                'en'          => 'Student Reports Officer',
                'ar'          => 'مسؤول تقارير الطلاب',
                'description' => 'Tracks attendance and evaluations. Sending a monthly report to students and parents is queued for the Reports Supervisor.',
                'permissions' => $this->perms([
                    'students.manage', 'attendance.manage', 'evaluations.manage',
                    'graduated.manage', 'promotion.manage',
                ]),
            ],

            'hr_manager' => [
                'en'          => 'HR Manager',
                'ar'          => 'مدير الموارد البشرية',
                'description' => 'Maintains teacher records. Editing a teacher is sensitive and is queued for the Registrar.',
                'permissions' => $this->perms([
                    'teachers.manage', 'specializations.manage',
                ]),
            ],

            // ---- reviewers: hold <module>.approve, never <module>.manage ----
            'finance_director' => [
                'en'          => 'Finance Director',
                'ar'          => 'مدير الإدارة المالية',
                'description' => 'Approves salary payments and watches the finance reports. Cannot trigger a payment himself.',
                'permissions' => $this->perms([
                    'pending_requests.manage', 'salaries.approve',
                    'financial_reports.manage', 'accounting_reports.manage', 'receipts.manage',
                ]),
            ],

            'dean_of_studies' => [
                'en'          => 'Dean of Studies',
                'ar'          => 'وكيل الدراسات',
                'description' => 'Approves publishing paper-exam marks to students and parents.',
                'permissions' => $this->perms([
                    'pending_requests.manage', 'paper_exams.approve',
                    'students.manage', 'grades.manage', 'sections.manage', 'subjects.manage',
                ]),
            ],

            'reports_supervisor' => [
                'en'          => 'Reports Supervisor',
                'ar'          => 'مشرف التقارير',
                'description' => 'Approves sending the monthly student reports.',
                'permissions' => $this->perms([
                    'pending_requests.manage', 'student_reports.approve',
                    'students.manage', 'attendance.manage', 'evaluations.manage',
                ]),
            ],

            'registrar' => [
                'en'          => 'School Registrar',
                'ar'          => 'مسجل المدرسة',
                'description' => 'Approves edits to teacher records.',
                'permissions' => $this->perms([
                    'pending_requests.manage', 'teachers.approve',
                    'teachers.manage', 'specializations.manage',
                ]),
            ],

            'principal' => [
                'en'          => 'Principal',
                'ar'          => 'المدير',
                'description' => 'Holds "approve all sensitive actions", so every request in the school lands in his queue and he can also act directly.',
                'permissions' => $this->perms([
                    'pending_requests.manage', 'approvals.sensitive', 'notifications.manage',
                    'students.manage', 'teachers.manage', 'graduated.manage', 'promotion.manage',
                    'grades.manage', 'sections.manage', 'subjects.manage', 'paper_exams.manage',
                    'salaries.manage', 'evaluations.manage', 'complaints.manage',
                    'library.manage', 'online_classes.manage',
                ]),
            ],

            // ---- unrelated / restricted personas ----
            'transport_manager' => [
                'en'          => 'Transport Manager',
                'ar'          => 'مدير المواصلات',
                'description' => 'Runs the bus fleet only. Useful to prove that a module owner sees nothing outside their module.',
                'permissions' => $this->perms([
                    'transportation.view', 'transportation.buses.manage', 'transportation.routes.manage',
                    'transportation.students.manage', 'transportation.trips.manage',
                    'transportation.live_tracking.view', 'transportation.history.view',
                ]),
            ],

            'observer' => [
                'en'          => 'Read-only Observer',
                'ar'          => 'مراجع للاطلاع فقط',
                'description' => 'Dashboard only. Every other tab and endpoint answers 403, and the pending-requests screen stays empty.',
                'permissions' => $this->perms([]),
            ],
        ];
    }

    private function seedRoles(School $school): array
    {
        // Match on the translated English name in PHP rather than a JSON where() so the
        // lookup behaves the same on MySQL and SQLite.
        $existing = [];
        foreach (Role::where('school_id', $school->id)->get() as $role) {
            $en = $role->getTranslations('name')['en'] ?? null;
            if ($en !== null) {
                $existing[$en] = $role;
            }
        }

        $roles = [];
        foreach ($this->roleDefinitions() as $key => $definition) {
            $attributes = [
                'name'        => ['en' => $definition['en'], 'ar' => $definition['ar']],
                'description' => $definition['description'],
                'permissions' => $definition['permissions'],
                'is_active'   => true,
                'school_id'   => $school->id,
            ];

            $roles[$key] = isset($existing[$definition['en']])
                ? tap($existing[$definition['en']])->update($attributes)
                : Role::create($attributes);
        }

        return $roles;
    }

    // ------------------------------------------------------------------
    // Users
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{name: string, email: string, role: string, salary?: float|null}>
     */
    private function userDefinitions(): array
    {
        return [
            'administrator' => [
                'name' => 'Demo School Administrator', 'email' => 'admin@school.local',
                'role' => 'administrator', 'salary' => 3000.00,
            ],
            'finance_officer' => [
                'name' => 'Finance Officer', 'email' => 'finance@school.local',
                'role' => 'finance_officer', 'salary' => 4200.00,
            ],
            'finance_director' => [
                'name' => 'Finance Director', 'email' => 'cfo@school.local',
                'role' => 'finance_director', 'salary' => 6500.00,
            ],
            'academic_coordinator' => [
                'name' => 'Academic Coordinator', 'email' => 'academic@school.local',
                'role' => 'academic_coordinator', 'salary' => 4800.00,
            ],
            'dean_of_studies' => [
                'name' => 'Dean of Studies', 'email' => 'dean@school.local',
                'role' => 'dean_of_studies', 'salary' => 5800.00,
            ],
            'reports_officer' => [
                'name' => 'Student Reports Officer', 'email' => 'reports@school.local',
                'role' => 'reports_officer', 'salary' => 3600.00,
            ],
            'reports_supervisor' => [
                'name' => 'Reports Supervisor', 'email' => 'supervisor@school.local',
                'role' => 'reports_supervisor', 'salary' => 5100.00,
            ],
            'hr_manager' => [
                'name' => 'HR Manager', 'email' => 'hr@school.local',
                'role' => 'hr_manager', 'salary' => 4400.00,
            ],
            'registrar' => [
                'name' => 'School Registrar', 'email' => 'registrar@school.local',
                'role' => 'registrar', 'salary' => 4700.00,
            ],
            'principal' => [
                'name' => 'Principal', 'email' => 'principal@school.local',
                'role' => 'principal', 'salary' => 9000.00,
            ],
            'transport_manager' => [
                'name' => 'Transport Manager', 'email' => 'transport@school.local',
                'role' => 'transport_manager', 'salary' => 3200.00,
            ],
            'observer' => [
                'name' => 'Read-only Observer', 'email' => 'viewer@school.local',
                'role' => 'observer',
            ],
        ];
    }

    private function seedUsers(School $school, array $roles): array
    {
        $users = [];

        foreach ($this->userDefinitions() as $key => $definition) {
            $user = User::updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name'           => $definition['name'],
                    'password'       => Hash::make(self::PASSWORD),
                    'school_id'      => $school->id,
                    'is_super_admin' => false,
                    'salary'         => $definition['salary'] ?? null,
                ]
            );

            // sync() keeps a single role per demo account, which is what makes the
            // requester/reviewer split above reproducible.
            $user->roles()->sync([$roles[$definition['role']]->id]);

            $users[$key] = $user;
        }

        return $users;
    }

    // ------------------------------------------------------------------
    // A queue that is not empty on first login
    // ------------------------------------------------------------------

    /**
     * Two live requests, each of which still applies cleanly once approved: a teacher salary
     * revision and a monthly report for one real student of the demo school.
     */
    private function seedSampleRequests(School $school, array $users): void
    {
        $this->queueTeacherEdit($users['hr_manager']);
        $this->queueMonthlyReport($users['reports_officer']);
    }

    private function queueTeacherEdit(User $requester): void
    {
        // The teacher list deliberately keeps pre-scoring rows (school_id IS NULL) visible,
        // and most demo teachers still look like that, so the sample request uses one of them
        // rather than finding an empty school.
        $teacher = Teacher::query()
            ->where(fn ($q) => $q->where('school_id', $requester->school_id)->orWhereNull('school_id'))
            ->orderBy('id')
            ->first();

        if (! $teacher) {
            $this->command?->warn('  ! no teacher in this school, skipped the teacher-edit sample request');

            return;
        }

        $action = PendingActionRegistry::UPDATE_TEACHER;

        if (PendingRequest::ofRequester($requester)->where('action', $action)->where('status', PendingRequest::STATUS_PENDING)->exists()) {
            return;
        }

        $name = $teacher->getTranslations('Name');
        $payload = [
            'Email'             => $teacher->email,
            'Name_ar'           => $name['ar'] ?? $teacher->email,
            'Name_en'           => $name['en'] ?? null,
            'Specialization_id' => $teacher->Specialization_id,
            'Gender_id'         => $teacher->Gender_id,
            'Joining_Date'      => substr((string) $teacher->Joining_Date, 0, 10),
            'Address'           => $teacher->Address,
            // The queue payload is keyed the way TeacherProfileService validates it
            // ("Salary"), while the column itself is `salary`.
            'Salary'            => (float) $teacher->salary + 250,
        ];

        $this->guard(function () use ($requester, $teacher, $action, $payload, $name) {
            PendingRequestService::defer($action, [
                'entity'         => $teacher,
                'title'          => 'Annual salary revision for '.($name['en'] ?? $teacher->email),
                'title_ar'       => 'مراجعة الراتب السنوية لـ '.($name['ar'] ?? $teacher->email),
                'description'    => 'Raise the yearly salary of '.($name['en'] ?? $teacher->email).' as part of the annual review.',
                'payload'        => $payload,
                'reason'         => 'Annual performance review.',
                'priority'       => PendingRequest::PRIORITY_NORMAL,
                'endpoint'       => 'teachers.update',
            ], $requester);
        }, 'teacher-edit sample request');
    }

    private function queueMonthlyReport(User $requester): void
    {
        $student = Student::query()
            ->where(fn ($q) => $q->where('school_id', $requester->school_id)->orWhereNull('school_id'))
            ->orderBy('id')
            ->first();

        if (! $student) {
            $this->command?->warn('  ! no student in this school, skipped the report sample request');

            return;
        }

        $action = PendingActionRegistry::SEND_REPORT;

        if (PendingRequest::ofRequester($requester)->where('action', $action)->where('status', PendingRequest::STATUS_PENDING)->exists()) {
            return;
        }

        $month = (int) now()->subMonth()->month;
        $year = (int) now()->subMonth()->year;
        $name = $student->getTranslations('name');

        $this->guard(function () use ($requester, $student, $action, $month, $year, $name) {
            PendingRequestService::defer($action, [
                'entity'         => $student,
                'title'          => 'Send monthly report of '.($name['en'] ?? $student->email).' ('.$month.'/'.$year.')',
                'title_ar'       => 'إرسال التقرير الشهري للطالب '.($name['ar'] ?? $student->email).' ('.$month.'/'.$year.')',
                'description'    => 'Generate and send the monthly report to the student and their parents.',
                'payload'        => ['month' => $month, 'year' => $year, 'student_id' => (int) $student->getKey()],
                'reason'         => 'End-of-month reporting run.',
                'priority'       => PendingRequest::PRIORITY_NORMAL,
                'endpoint'       => 'student-reports.generate',
            ], $requester);
        }, 'report sample request');
    }

    /**
     * Sample data must never take the whole seed down: report and carry on.
     */
    private function guard(callable $callback, string $label): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            $this->command?->warn('  ! could not create the '.$label.': '.$e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Permission helpers
    // ------------------------------------------------------------------

    /**
     * Turn a list of permission keys into the role's stored map, refusing typos so a
     * mistyped key cannot silently hand somebody less access than intended.
     *
     * @param  array<int, string>  $keys
     * @return array<string, bool>
     */
    private function perms(array $keys): array
    {
        $known = array_column(PermissionRegistry::all(), 'key');
        $unknown = array_values(array_diff(array_merge(self::BASE, $keys), $known));

        if ($unknown !== []) {
            throw new InvalidArgumentException(
                'Unknown permission key(s): '.implode(', ', $unknown).'. Check App\Services\Roles\PermissionRegistry.'
            );
        }

        return array_fill_keys(array_merge(self::BASE, $keys), true);
    }

    /**
     * @return array<string, bool>
     */
    private function everyPermission(): array
    {
        return array_fill_keys(array_column(PermissionRegistry::all(), 'key'), true);
    }

    // ------------------------------------------------------------------
    // Wiring / output
    // ------------------------------------------------------------------

    private function resolveSchool(?string $school): School
    {
        if ($school !== null && $school !== '') {
            return School::findOrFail((int) $school);
        }

        $model = School::where('email', self::SCHOOL_EMAIL)->first();

        if (! $model) {
            throw new RuntimeException(
                'No school with the e-mail '.self::SCHOOL_EMAIL.' was found. Run database/seeds/UserSeeder.php first '
                .'(php artisan db:seed --class="\UserSeeder"), or pass --school=<id>.'
            );
        }

        return $model;
    }

    /**
     * Read a CLI option, tolerating being invoked from another seeder.
     */
    private function flag(string $key): bool
    {
        if ($this->command === null || ! $this->command->getDefinition()->hasOption($key)) {
            return false;
        }

        return (bool) $this->option($key);
    }

    private function report(School $school, array $users, bool $withPending): void
    {
        $this->command?->info('Demo staff seeded for: '.($school->getTranslations('name')['en'] ?? $school->name).' (#'.$school->id.')');
        $this->command?->newLine();
        $this->command?->table(
            ['Account', 'Role', 'Permission keys', 'Queues', 'Approves'],
            $this->reportRows($users)
        );
        $this->command?->line('  password for every account: '.self::PASSWORD);
        $this->command?->line('  "Queues" lists the sensitive actions an account lacks approval for; the matching module');
        $this->command?->line('  permission (salaries.manage, paper_exams.manage, ...) is what lets it reach the queue at all.');
        $this->command?->line('  sample pending requests: '.($withPending
            ? PendingRequest::where('school_id', $school->id)->where('status', PendingRequest::STATUS_PENDING)->count()
            : 'skipped'));
        $this->command?->newLine();
    }

    private function reportRows(array $users): array
    {
        $rows = [];

        foreach ($this->userDefinitions() as $key => $definition) {
            /** @var User $user */
            $user = $users[$key];
            $permissions = $user->effectivePermissions();
            $granted = $permissions === ['*'] ? 'all' : (string) count($permissions);

            $rows[] = [
                $definition['email'],
                $user->roles()->first()?->getTranslations('name')['en'] ?? '-',
                $granted,
                $this->capabilities($user, true),
                $this->capabilities($user, false),
            ];
        }

        return $rows;
    }

    /**
     * Which sensitive actions this account ends up queueing / approving.
     *
     * Both columns are derived from the real authorisation helpers so the printed table
     * can never drift from the service: `approvals.sensitive` is a blanket approval, which
     * is why the principal and the administrator execute directly instead of queueing.
     */
    private function capabilities(User $user, bool $queueing): string
    {
        $caps = [];

        foreach (array_keys(PendingActionRegistry::all()) as $key) {
            if (PendingRequestService::requiresApproval($user, $key) === $queueing) {
                $caps[] = (string) (PendingActionRegistry::get($key)['label_en'] ?? $key);
            }
        }

        return $caps === [] ? '-' : implode(', ', $caps);
    }
}
