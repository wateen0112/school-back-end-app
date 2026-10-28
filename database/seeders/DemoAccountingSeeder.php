<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FundAccount;
use App\Models\SalaryPayment;
use App\Models\School;
use App\Models\Teacher;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Sample accounting data (expense categories, expenses, salary payments and the
 * matching fund-account movements) so the finance/accounting screens of the
 * mobile app and the dashboard are not empty on a fresh demo database.
 *
 * Runs after DemoSchoolStaffSeeder so the accountant / finance accounts exist.
 * Idempotent: expenses are matched by reference number and salaries by their
 * employee + period, so re-running updates in place.
 *
 * Usage:
 *   php artisan db:seed --class="Database\Seeders\DemoAccountingSeeder"
 */
class DemoAccountingSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('email', 'admin@school.local')->first() ?? School::first();

        if (! $school) {
            $this->command?->warn('No school found — run database/seeds/UserSeeder.php first.');

            return;
        }

        $creator = User::where('email', 'accountant@school.local')->first()
            ?? User::where('email', 'finance@school.local')->first()
            ?? User::where('email', 'admin@school.local')->first();

        $categories = $this->seedCategories();
        $this->seedExpenses($categories, $creator);
        $this->seedSalaries();
    }

    /**
     * @return array<string, ExpenseCategory>
     */
    private function seedCategories(): array
    {
        $definitions = [
            'utilities'   => ['en' => 'Utilities', 'ar' => 'مرافق', 'icon' => 'bolt'],
            'maintenance' => ['en' => 'Maintenance', 'ar' => 'صيانة', 'icon' => 'build'],
            'supplies'    => ['en' => 'Supplies', 'ar' => 'لوازم', 'icon' => 'shopping_bag'],
            'transport'   => ['en' => 'Transport', 'ar' => 'نقل', 'icon' => 'directions_bus'],
            'events'      => ['en' => 'Events', 'ar' => 'فعاليات', 'icon' => 'celebration'],
        ];

        $categories = [];

        foreach ($definitions as $key => $definition) {
            $categories[$key] = ExpenseCategory::updateOrCreate(
                ['name->en' => $definition['en']],
                [
                    'name' => ['en' => $definition['en'], 'ar' => $definition['ar']],
                    'icon' => $definition['icon'],
                ]
            );
        }

        return $categories;
    }

    /**
     * @param array<string, ExpenseCategory> $categories
     */
    private function seedExpenses(array $categories, ?User $creator): void
    {
        $year = Carbon::now()->year;

        $definitions = [
            ['utilities',   'Electricity bill — January',  1250.00, 1],
            ['maintenance', 'Classroom AC repair',          860.50, 2],
            ['supplies',    'Office stationery',            430.75, 3],
            ['transport',   'School bus fuel',             2100.00, 4],
            ['events',      'Science fair',                 980.00, 5],
            ['utilities',   'Water bill — spring',          540.00, 6],
            ['maintenance', 'Roof waterproofing',          3400.00, 7],
            ['supplies',    'Lab equipment',               1750.25, 8],
            ['transport',   'Bus maintenance',             1220.00, 9],
            ['events',      'Sports day',                   760.00, 10],
            ['supplies',    'Library books',                990.00, 11],
            ['utilities',   'Internet & phone',             610.00, 12],
        ];

        foreach ($definitions as $index => [$categoryKey, $title, $amount, $month]) {
            $reference = sprintf('EXP-%d-%02d-%02d', $year, $month, $index + 1);

            $expense = Expense::updateOrCreate(
                ['reference_no' => $reference],
                [
                    'category_id'  => $categories[$categoryKey]->id,
                    'title'        => $title,
                    'amount'       => $amount,
                    'expense_date' => Carbon::create($year, $month, min(12 + $index, 27))->toDateString(),
                    'description'  => $title,
                    'created_by'   => $creator?->id,
                ]
            );

            FundAccount::updateOrCreate(
                ['expense_id' => $expense->id],
                [
                    'date'        => $expense->expense_date,
                    'credit'      => $amount,
                    'Debit'       => 0,
                    'description' => 'Expense: '.$title,
                ]
            );
        }
    }

    private function seedSalaries(): void
    {
        $candidates = $this->salaryCandidates();

        if ($candidates === []) {
            return;
        }

        $now = Carbon::now();

        // Two closed months (paid) and the current month (still pending), which
        // gives the dashboard its "upcoming salaries" list.
        $periods = [
            [$now->copy()->subMonths(2), 'paid'],
            [$now->copy()->subMonth(), 'paid'],
            [$now->copy(), 'pending'],
        ];

        foreach ($candidates as $candidate) {
            foreach ($periods as [$period, $status]) {
                $dueDate = $period->copy()->endOfMonth();

                $payment = SalaryPayment::updateOrCreate(
                    [
                        'employee_type' => $candidate['employee_type'],
                        'employee_id'   => $candidate['employee_id'],
                        'period_year'   => (int) $period->year,
                        'period_month'  => (int) $period->month,
                        'source'        => 'monthly',
                    ],
                    [
                        'amount'         => $candidate['salary'],
                        'due_date'       => $dueDate->toDateString(),
                        'paid_at'        => $status === 'paid' ? $dueDate->toDateString() : null,
                        'status'         => $status,
                        'payment_method' => 'bank_transfer',
                    ]
                );

                if ($status === 'paid') {
                    FundAccount::updateOrCreate(
                        ['salary_payment_id' => $payment->id],
                        [
                            'date'        => $payment->paid_at,
                            'credit'      => $payment->amount,
                            'Debit'       => 0,
                            'description' => 'Salary paid: '.$payment->employee_name,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Mirrors SalaryController::payrollCandidates(): monthly teachers plus staff
     * users that have a salary and are not super admins.
     *
     * @return array<int, array{employee_type: string, employee_id: int, salary: float}>
     */
    private function salaryCandidates(): array
    {
        $teachers = Teacher::whereNotNull('salary')
            ->where(function ($query) {
                $query->whereNull('salary_type')->orWhere('salary_type', 'monthly');
            })
            ->get(['id', 'salary'])
            ->map(fn ($teacher) => [
                'employee_type' => 'teacher',
                'employee_id'   => $teacher->id,
                'salary'        => (float) $teacher->salary,
            ])
            ->all();

        $staff = User::whereNotNull('salary')
            ->where('is_super_admin', false)
            ->get(['id', 'salary'])
            ->map(fn ($user) => [
                'employee_type' => 'staff',
                'employee_id'   => $user->id,
                'salary'        => (float) $user->salary,
            ])
            ->all();

        return array_merge($teachers, $staff);
    }
}
