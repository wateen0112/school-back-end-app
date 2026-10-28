<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Write an audit entry (best-effort; never breaks the request).
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        $entityId = null,
        ?string $description = null,
        array $meta = []
    ): void {
        try {
            $user = static::actingUser();

            AuditLog::create([
                'user_id' => $user?->id,
                'user_type' => $user ? get_class($user) : null,
                'school_id' => static::resolveSchoolId($user),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'meta' => $meta,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break a request.
        }
    }

    /**
     * Write a data-change audit with before/after snapshots.
     */
    public static function logChange(
        string $action,
        Model $entity,
        array $old,
        array $new,
        ?string $reason = null,
        ?string $approvalRef = null,
        array $extraMeta = []
    ): void {
        try {
            $user = static::actingUser();
            $diff = self::diffArrays($old, $new);

            AuditLog::create([
                'user_id' => $user?->id,
                'user_type' => $user ? get_class($user) : null,
                'school_id' => static::resolveSchoolId($user) ?? ($entity->getAttribute('school_id') ?? null),
                'action' => $action,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->getKey(),
                'description' => static::buildChangeDescription($action, $entity, $diff),
                'meta' => array_merge($extraMeta, ['changed' => array_keys($diff)]),
                'old_values' => $old,
                'new_values' => $new,
                'reason' => $reason,
                'approval_reference' => $approvalRef,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break a request.
        }
    }

    private static function actingUser(): ?Model
    {
        return Auth::guard('api')->user()
            ?? Auth::guard('web')->user()
            ?? Auth::guard('teacher')->user()
            ?? Auth::guard('student')->user()
            ?? Auth::guard('parent')->user();
    }

    public static function resolveSchoolId(?Model $user): ?int
    {
        if (! $user) {
            return null;
        }

        // User (admin) model
        if ($user instanceof \App\User || $user instanceof \App\Models\School) {
            return $user->getAttribute('school_id');
        }

        // Student / Teacher / My_Parent — school_id added by migration
        $val = $user->getAttribute('school_id');

        return $val !== null ? (int) $val : null;
    }

    private static function diffArrays(array $old, array $new): array
    {
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($allKeys as $key) {
            $o = $old[$key] ?? null;
            $n = $new[$key] ?? null;
            if ($o !== $n) {
                $diff[$key] = ['old' => $o, 'new' => $n];
            }
        }

        return $diff;
    }

    /** Arabic names for audited entities; falls back to the class basename. */
    private const ENTITY_NAMES_AR = [
        'Student' => 'الطالب',
        'Teacher' => 'المعلم',
        'My_Parent' => 'ولي الأمر',
        'User' => 'المستخدم',
        'Fee_invoice' => 'فاتورة الرسوم',
        'PaymentStudent' => 'سند قبض الطالب',
        'ReceiptStudent' => 'إيصال الطالب',
        'ProcessingFee' => 'رسوم المعالجة',
        'Expense' => 'المصروف',
        'SalaryPayment' => 'دفع الراتب',
        'Attendance' => 'الحضور',
        'Subject' => 'المادة الدراسية',
        'Classroom' => 'الفصل الدراسي',
        'Section' => 'الشعبة',
        'School' => 'المدرسة',
        'Role' => 'الدور',
        'Announcement' => 'الإعلان',
        'SchoolBranch' => 'فرع المدرسة',
        'Integration' => 'التكامل',
        'PermissionTemplate' => 'قالب الصلاحيات',
        'Plan' => 'خطة الاشتراك',
        'SecurityPolicy' => 'سياسة الأمان',
        'Subscription' => 'الاشتراك',
        'PlatformSetting' => 'إعدادات المنصة',
    ];

    private static function buildChangeDescription(string $action, Model $entity, array $diff): string
    {
        $name = self::ENTITY_NAMES_AR[class_basename($entity)] ?? class_basename($entity);
        $suffix = str_contains($action, '.') ? substr($action, (int) strrpos($action, '.') + 1) : $action;

        switch ($suffix) {
            case 'created':
                return "تم إنشاء {$name} رقم {$entity->getKey()}";
            case 'deleted':
                return "تم حذف {$name} رقم {$entity->getKey()}";
            case 'updated':
            default:
                $changedKeys = implode('، ', array_keys($diff));
                return "تم تعديل {$name} رقم {$entity->getKey()} — الحقول المتغيرة: [{$changedKeys}]";
        }
    }
}
