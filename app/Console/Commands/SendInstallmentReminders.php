<?php

namespace App\Console\Commands;

use App\Models\StudentInstallment;
use App\Services\InstallmentNotificationService;
use Illuminate\Console\Command;

class SendInstallmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send installment reminder and overdue notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $notificationService = new InstallmentNotificationService();

        $reminderInstallments = StudentInstallment::where('status', '!=', 'paid')
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '>=', now()->format('Y-m-d'))
            ->whereDate('due_date', '<=', now()->addDays(3)->format('Y-m-d'))
            ->whereNull('reminder_sent_at')
            ->get();

        $this->info("Sending reminders for {$reminderInstallments->count()} installments.");

        foreach ($reminderInstallments as $studentInstallment) {
            $result = $notificationService->sendReminder($studentInstallment);
            if ($result['success']) {
                $this->info("Reminder sent for student installment #{$studentInstallment->id}");
            } else {
                $this->error("Failed to send reminder for student installment #{$studentInstallment->id}: " . ($result['message'] ?? ''));
            }
        }

        $overdueInstallments = StudentInstallment::where('status', '!=', 'paid')
            ->where('remaining_amount', '>', 0)
            ->whereDate('due_date', '<', now()->format('Y-m-d'))
            ->whereNull('overdue_sent_at')
            ->get();

        $this->info("Sending overdue notices for {$overdueInstallments->count()} installments.");

        foreach ($overdueInstallments as $studentInstallment) {
            $result = $notificationService->sendOverdue($studentInstallment);
            if ($result['success']) {
                $this->info("Overdue notice sent for student installment #{$studentInstallment->id}");
            } else {
                $this->error("Failed to send overdue notice for student installment #{$studentInstallment->id}: " . ($result['message'] ?? ''));
            }
        }

        return 0;
    }
}
