<?php

namespace App\Services\Transportation;

use App\Models\Notification;
use App\Models\Transportation\TransportationSetting;
use App\Models\NotificationRecipient;
use App\Services\FcmNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Transportation notifications ride on the EXISTING notification
 * infrastructure: Notification + NotificationRecipient (polymorphic) +
 * FcmNotificationService. Nothing here duplicates that stack.
 *
 * All parent-facing texts are bilingual {en, ar} following the
 * Notification::localizeText convention.
 */
class TransportationNotifier
{
    /**
     * Reuses the existing notification enum (general|targeted|role) as-is —
     * transportation alerts are targeted notifications; the event name lives
     * in the data payload so clients can filter/render them specifically.
     */
    public const TYPE = 'targeted';

    /**
     * @param array<string, string> $texts ['en' => ..., 'ar' => ...]
     * @param array<int, Model>     $recipients
     */
    public function send(array $texts, array $recipients, array $data = [], ?Model $actor = null): ?Notification
    {
        if (empty($recipients)) {
            return null;
        }

        $notification = Notification::create([
            'title' => json_encode(['en' => $texts['en'], 'ar' => $texts['ar']], JSON_UNESCAPED_UNICODE),
            'body' => json_encode(['en' => $texts['body_en'] ?? $texts['en'], 'ar' => $texts['body_ar'] ?? $texts['ar']], JSON_UNESCAPED_UNICODE),
            'type' => self::TYPE,
            'sender_id' => $actor?->id,
            'sender_type' => $actor ? get_class($actor) : null,
            'data' => $data ?: null,
        ]);

        $rows = array_map(fn ($r) => [
            'notification_id' => $notification->id,
            'recipient_id' => $r->id,
            'recipient_type' => get_class($r),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $recipients);

        foreach (array_chunk($rows, 500) as $chunk) {
            NotificationRecipient::insert($chunk);
        }

        // Best-effort FCM push — never breaks the request.
        try {
            $targets = array_map(fn ($r) => ['id' => $r->id, 'type' => get_class($r)], $recipients);
            app(FcmNotificationService::class)->sendToUsers(
                $targets,
                $texts['en'],
                $texts['body_en'] ?? $texts['en'],
                ['type' => 'transportation'] + $data
            );
        } catch (\Throwable $e) {
            \Log::warning('Transportation FCM send failed: ' . $e->getMessage());
        }

        return $notification;
    }

    /** Parents of one student (validated by callers against school scope). */
    public function parentsOfStudent($student): array
    {
        $parent = $student?->parent;
        return $parent ? [$parent] : [];
    }

    public function parentsOfStudents(iterable $students): array
    {
        $parents = [];
        foreach ($students as $student) {
            foreach ($this->parentsOfStudent($student) as $parent) {
                $parents[$parent->id] = $parent;
            }
        }
        return array_values($parents);
    }

    // --------------------------------------------------------------- events

    public function notifyTripStarted($trip): void
    {
        $students = $trip->tripStudents->loadMissing('student.parent')->pluck('student');
        $this->send(
            [
                'en' => 'Bus trip started',
                'ar' => 'بدت رحلة الحافلة',
                'body_en' => "The bus for route {$trip->route?->name} is on its way.",
                'body_ar' => "الحافلة للخط {$trip->route?->name} في الطريق إليكم.",
            ],
            $this->parentsOfStudents($students),
            ['event' => 'TRIP_STARTED', 'trip_id' => $trip->id]
        );
    }

    public function notifyApproaching($trip, $tripStudent, bool $isPickup): void
    {
        $student = $tripStudent->student;
        $name = is_string($student?->name) ? $student->name : ($student?->name['en'] ?? $student?->name ?? '');

        $this->send(
            $isPickup ? [
                'en' => 'Bus approaching pickup',
                'ar' => 'الحافلة تقترب من موقع الاستلام',
                'body_en' => "The bus is approaching the pickup location for {$name}.",
                'body_ar' => "الحافلة تقترب من موقع استلام {$name}.",
            ] : [
                'en' => 'Bus approaching drop-off',
                'ar' => 'الحافلة تقترب من موقع التوصيل',
                'body_en' => "The bus is approaching the drop-off location for {$name}.",
                'body_ar' => "الحافلة تقترب من موقع توصيل {$name}.",
            ],
            $this->parentsOfStudent($student),
            [
                'event' => $isPickup ? 'BUS_APPROACHING_PICKUP' : 'BUS_APPROACHING_DROPOFF',
                'trip_id' => $trip->id,
                'student_id' => $tripStudent->student_id,
            ]
        );
    }

    public function notifyPickedUp($trip, $tripStudent): void
    {
        $student = $tripStudent->student;
        $name = is_string($student?->name) ? $student->name : ($student?->name['en'] ?? $student?->name ?? '');

        $this->send(
            [
                'en' => 'Student picked up',
                'ar' => 'تم ركوب الطالب',
                'body_en' => "{$name} has been picked up and is on the bus.",
                'body_ar' => "تم ركوب {$name} وهو في الحافلة الآن.",
            ],
            $this->parentsOfStudent($student),
            ['event' => 'STUDENT_PICKED_UP', 'trip_id' => $trip->id, 'student_id' => $tripStudent->student_id]
        );
    }

    public function notifyDroppedOff($trip, $tripStudent): void
    {
        $student = $tripStudent->student;
        $name = is_string($student?->name) ? $student->name : ($student?->name['en'] ?? $student?->name ?? '');

        $this->send(
            [
                'en' => 'Student dropped off',
                'ar' => 'تم توصيل الطالب',
                'body_en' => "{$name} has been dropped off.",
                'body_ar' => "تم توصيل {$name} ونزوله من الحافلة.",
            ],
            $this->parentsOfStudent($student),
            ['event' => 'STUDENT_DROPPED_OFF', 'trip_id' => $trip->id, 'student_id' => $tripStudent->student_id]
        );
    }

    public function notifyTripEnded($trip): void
    {
        $students = $trip->tripStudents->loadMissing('student.parent')->pluck('student');
        $this->send(
            [
                'en' => 'Bus trip completed',
                'ar' => 'اكتملت رحلة الحافلة',
                'body_en' => "The bus trip for route {$trip->route?->name} has been completed.",
                'body_ar' => "اكتملت رحلة الحافلة للخط {$trip->route?->name}.",
            ],
            $this->parentsOfStudents($students),
            ['event' => 'TRIP_ENDED', 'trip_id' => $trip->id]
        );
    }

    public function notifyTripCancelled($trip, ?string $reason = null): void
    {
        $students = $trip->tripStudents->loadMissing('student.parent')->pluck('student');
        $this->send(
            [
                'en' => 'Bus trip cancelled',
                'ar' => 'تم إلغاء رحلة الحافلة',
                'body_en' => 'The bus trip has been cancelled.' . ($reason ? " Reason: {$reason}" : ''),
                'body_ar' => 'تم إلغاء رحلة الحافلة.' . ($reason ? " السبب: {$reason}" : ''),
            ],
            $this->parentsOfStudents($students),
            ['event' => 'TRIP_CANCELLED', 'trip_id' => $trip->id]
        );
    }

    /**
     * Emergency: notify school admins, plus trip parents when the school
     * settings allow it. Always recorded in trip events (caller).
     */
    public function notifyEmergency($trip, $driver): void
    {
        $recipients = [];

        // School admins (staff users of the same school).
        $admins = \App\User::where('school_id', $trip->school_id)->get();
        foreach ($admins as $admin) {
            $recipients['admin:' . $admin->id] = $admin;
        }

        $settings = TransportationSetting::forSchool($trip->school_id);
        if ($settings->notify_parents_on_emergency) {
            $students = $trip->tripStudents->loadMissing('student.parent')->pluck('student');
            foreach ($this->parentsOfStudents($students) as $parent) {
                $recipients['parent:' . $parent->id] = $parent;
            }
        }

        $driverName = $driver?->name ?? '';
        $this->send(
            [
                'en' => 'EMERGENCY - bus trip',
                'ar' => 'حالة طوارئ - رحلة حافلة',
                'body_en' => "Emergency alert from driver {$driverName} on route {$trip->route?->name}.",
                'body_ar' => "إنذار طوارئ من السائق {$driverName} على الخط {$trip->route?->name}.",
            ],
            array_values($recipients),
            ['event' => 'EMERGENCY', 'trip_id' => $trip->id]
        );
    }
}
