<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\StudentReportService;
use Illuminate\Console\Command;

class GenerateMonthlyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate-monthly
                            {--month= : Month to generate for (1-12). Defaults to the previous month.}
                            {--year= : Year to generate for. Defaults to the previous month year.}
                            {--student= : Generate for a single student ID only.}
                            {--no-notify : Skip sending notifications to parents/students.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate monthly student progress reports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $previous = now()->subMonth();

        $month = $this->option('month') !== null
            ? (int) $this->option('month')
            : (int) $previous->format('n');

        $year = $this->option('year') !== null
            ? (int) $this->option('year')
            : (int) $previous->format('Y');

        if ($month < 1 || $month > 12) {
            $this->error('Month must be between 1 and 12.');

            return 1;
        }

        $sendNotifications = ! $this->option('no-notify');
        $service = new StudentReportService();

        $studentId = $this->option('student');

        if ($studentId !== null) {
            if (! Student::where('id', $studentId)->exists()) {
                $this->error("Student #{$studentId} not found.");

                return 1;
            }

            $this->info("Generating report for student #{$studentId} — {$month}/{$year}");

            $report = $service->generateForStudent((int) $studentId, $month, $year, $sendNotifications);

            if ($report === null) {
                $this->warn("No data found for student #{$studentId} in {$month}/{$year}.");
            } else {
                $this->info("Report #{$report->id} generated with progress score {$report->progress_score}%.");
            }

            return 0;
        }

        $this->info("Generating monthly reports for {$month}/{$year} ...");

        $result = $service->generateForAll($month, $year, $sendNotifications);

        $this->info("Generated {$result['generated']} reports.");
        $this->info("Skipped {$result['skipped']} students without data.");
        $this->warn("Failed for {$result['failed']} students.");

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['failed'] > 0 ? 1 : 0;
    }
}