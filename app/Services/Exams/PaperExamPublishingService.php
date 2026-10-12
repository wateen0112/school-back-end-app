<?php

namespace App\Services\Exams;

use App\Models\My_Parent;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\PaperExam;
use App\Models\Student;
use App\Services\FcmNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Publishing a paper exam exposes its marks to students and their parents, so
 * it lives behind the sensitive-action approval flow. The logic is kept here so
 * the endpoint and the approval replay behave identically.
 */
class PaperExamPublishingService
{
    /**
     * Publish the exam and notify every student (and their parent) that has a mark.
     *
     * @throws ConflictHttpException when the exam is already published.
     */
    public function publish(PaperExam $exam, ?Model $actor = null): PaperExam
    {
        $exam->loadMissing([
            'subject', 'teacher', 'grade', 'classroom', 'section',
            'marks.student.parent',
        ]);

        if ($exam->status === 'published') {
            throw new ConflictHttpException('Exam already published');
        }

        $exam->update(['status' => 'published']);

        $title = 'نتائج الاختبار: '.$exam->title;
        $body = 'تم نشر نتائج اختبار '.$exam->title.' لمادة '.($exam->subject->Name ?? '')
            .'. يمكنك الآن الاطلاع على العلامات.';

        $notification = Notification::create([
            'title'       => $title,
            'body'        => $body,
            'type'        => 'targeted',
            'sender_id'   => $actor?->getKey(),
            'sender_type' => $actor ? get_class($actor) : null,
            'target_role' => null,
            'data'        => [
                'type'          => 'paper_exam_publish',
                'paper_exam_id' => $exam->id,
                'subject_id'    => $exam->subject_id,
            ],
        ]);

        $recipients = [];
        $fcmTargets = [];
        $now = now();

        foreach ($exam->marks as $mark) {
            $student = $mark->student;

            if (! $student) {
                continue;
            }

            $recipients[] = $this->recipientRow($notification->id, $student, Student::class, $now);
            $fcmTargets[] = ['id' => $student->id, 'type' => Student::class];

            if ($student->parent_id) {
                $recipients[] = $this->recipientRow($notification->id, $student, My_Parent::class, $now, $student->parent_id);
                $fcmTargets[] = ['id' => $student->parent_id, 'type' => My_Parent::class];
            }
        }

        if ($recipients !== []) {
            foreach (array_chunk($recipients, 500) as $chunk) {
                NotificationRecipient::insert($chunk);
            }
        }

        try {
            (new FcmNotificationService())->sendToUsers($fcmTargets, $title, $body, [
                'notification_id' => $notification->id,
                'type'            => 'paper_exam_publish',
                'paper_exam_id'   => $exam->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM send failed for paper exam publish: '.$e->getMessage());
        }

        return $exam->fresh();
    }

    private function recipientRow(int $notificationId, Student $student, string $type, $now, ?int $id = null): array
    {
        return [
            'notification_id' => $notificationId,
            'recipient_id'    => $id ?? $student->id,
            'recipient_type'  => $type,
            'is_read'         => false,
            'created_at'      => $now,
            'updated_at'      => $now,
        ];
    }
}