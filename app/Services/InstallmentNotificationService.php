<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\StudentInstallment;
use Illuminate\Support\Facades\DB;

class InstallmentNotificationService
{
    /**
     * Send a reminder notification for an installment due soon.
     */
    public function sendReminder(StudentInstallment $studentInstallment)
    {
        return $this->send($studentInstallment, 'installment_reminder');
    }

    /**
     * Send an overdue notification for a past-due installment.
     */
    public function sendOverdue(StudentInstallment $studentInstallment)
    {
        return $this->send($studentInstallment, 'installment_overdue');
    }

    private function send(StudentInstallment $studentInstallment, $type)
    {
        $student = $studentInstallment->student;
        $installment = $studentInstallment->installment;
        $plan = $studentInstallment->installmentPlan;

        if (! $student || ! $installment) {
            return ['success' => false, 'message' => 'Missing student or installment data'];
        }

        $studentName = is_array($student->name) ? ($student->name['ar'] ?? $student->name['en'] ?? '') : $student->name;
        $amount = number_format($studentInstallment->amount, 2);
        $dueDate = $studentInstallment->due_date ? $studentInstallment->due_date->format('Y-m-d') : '';

        $isReminder = $type === 'installment_reminder';

        $title = $isReminder
            ? 'تذكير بموعد دفعة رسوم دراسية'
            : 'تأخر سداد دفعة رسوم دراسية';

        $body = $isReminder
            ? "عزيزي/عزيزتي {$studentName}، تذكير باقتراب موعد سداد الدفعة {$installment->title} بمبلغ {$amount} بتاريخ {$dueDate}."
            : "عزيزي/عزيزتي {$studentName}، لقد تجاوز موعد سداد الدفعة {$installment->title} بمبلغ {$amount} المستحق بتاريخ {$dueDate}. يرجى السداد في أقرب وقت.";

        $data = [
            'type' => $type,
            'student_installment_id' => $studentInstallment->id,
            'installment_plan_id' => $studentInstallment->installment_plan_id,
        ];

        DB::beginTransaction();

        try {
            $notification = Notification::create([
                'title' => $title,
                'body' => $body,
                'type' => 'targeted',
                'sender_id' => null,
                'sender_type' => null,
                'target_role' => null,
                'data' => $data,
            ]);

            $recipients = [];
            $now = now();

            $recipients[] = [
                'notification_id' => $notification->id,
                'recipient_id' => $student->id,
                'recipient_type' => 'App\Models\Student',
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($student->parent_id && $student->parent) {
                $recipients[] = [
                    'notification_id' => $notification->id,
                    'recipient_id' => $student->parent_id,
                    'recipient_type' => 'App\Models\My_Parent',
                    'is_read' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            NotificationRecipient::insert($recipients);

            if ($isReminder) {
                $studentInstallment->reminder_sent_at = now();
            } else {
                $studentInstallment->overdue_sent_at = now();
            }
            $studentInstallment->save();

            DB::commit();

            $this->sendFcm($student, $title, $body, $data);

            return ['success' => true, 'notification' => $notification];
        } catch (\Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function sendFcm($student, $title, $body, array $data)
    {
        try {
            $fcmService = new FcmNotificationService();
            $targets = [
                ['id' => $student->id, 'type' => 'App\Models\Student'],
            ];

            if ($student->parent_id) {
                $targets[] = ['id' => $student->parent_id, 'type' => 'App\Models\My_Parent'];
            }

            $fcmService->sendToUsers($targets, $title, $body, $data);
        } catch (\Exception $e) {
            \Log::warning('Installment FCM send failed: ' . $e->getMessage());
        }
    }
}
