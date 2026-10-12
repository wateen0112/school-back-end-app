<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Quizze;
use App\Models\Student;
use App\Services\FcmNotificationService;

class QuizzeObserver
{
    /**
     * Handle the Quizze "created" event.
     *
     * @param  \App\Models\Quizze  $quizze
     * @return void
     */
    public function created(Quizze $quizze)
    {
        $locale = app()->getLocale();
        $quizNameAr = $quizze->getTranslation('name', 'ar') ?? $quizze->name;
        $quizNameEn = $quizze->getTranslation('name', 'en') ?? $quizze->name;

        // Push notification shown in the creation language.
        $title = $locale === 'ar'
            ? 'اختبار جديد متاح'
            : 'New exam available';

        $body = $locale === 'ar'
            ? "تم إضافة اختبار '{$quizNameAr}'. اضغط لبدء الاختبار."
            : "A new exam '{$quizNameEn}' has been added. Tap to start.";

        // Stored bilingually so the app can pick the right language later.
        $storedTitle = json_encode([
            'en' => 'New exam available',
            'ar' => 'اختبار جديد متاح',
        ], JSON_UNESCAPED_UNICODE);

        $storedBody = json_encode([
            'en' => "A new exam '{$quizNameEn}' has been added. Tap to start.",
            'ar' => "تم إضافة اختبار '{$quizNameAr}'. اضغط لبدء الاختبار.",
        ], JSON_UNESCAPED_UNICODE);

        $students = Student::where('Grade_id', $quizze->grade_id)
            ->where('Classroom_id', $quizze->classroom_id)
            ->where('section_id', $quizze->section_id)
            ->get();

        if ($students->isEmpty()) {
            return;
        }

        $notification = Notification::create([
            'title'       => $storedTitle,
            'body'        => $storedBody,
            'type'        => 'targeted',
            'sender_id'   => $quizze->teacher_id,
            'sender_type' => 'App\Models\Teacher',
            'target_role' => null,
            'data'        => [
                'type'      => 'exam',
                'quizze_id' => $quizze->id,
                'route'     => "/student/exams/{$quizze->id}/start",
            ],
        ]);

        $rows = [];
        $now = now();

        foreach ($students as $student) {
            $rows[] = [
                'notification_id' => $notification->id,
                'recipient_id'    => $student->id,
                'recipient_type'  => 'App\Models\Student',
                'is_read'         => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            NotificationRecipient::insert($chunk);
        }

        try {
            $fcmService = new FcmNotificationService();
            $targets = $students->map(function ($student) {
                return ['id' => $student->id, 'type' => 'App\Models\Student'];
            })->toArray();

            $fcmService->sendToUsers(
                $targets,
                $title,
                $body,
                [
                    'notification_id' => (string) $notification->id,
                    'type'            => 'exam',
                    'quizze_id'       => (string) $quizze->id,
                    'route'           => "/student/exams/{$quizze->id}/start",
                ]
            );
        } catch (\Exception $e) {
            \Log::warning('FCM exam notification failed: ' . $e->getMessage());
        }
    }
}
