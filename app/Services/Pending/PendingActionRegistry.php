<?php

namespace App\Services\Pending;

use Illuminate\Database\Eloquent\Model;

/**
 * Catalogue of the "sensitive actions" that cannot be performed directly by
 * anybody who is not an approver.
 *
 * A sensitive action attempted by a user who lacks the action's `permission`
 * key does not run: the caller turns it into a pending request instead (see
 * PendingRequestService::defer()), an approver decides, and PendingRequestService
 * runs the registered executor on approval.
 *
 * Definitions here carry the presentation + authorisation data only. The
 * executors (the code that actually performs the change) are attached by
 * \App\Providers\PendingRequestServiceProvider so this class stays free of
 * domain imports.
 */
class PendingActionRegistry
{
    /** Pay a salary payment (cash out of the fund ledger). */
    public const PAY_SALARY = 'pay_salary';

    /** Publish paper exam marks to students and their parents. */
    public const SEND_MARKS = 'send_marks';

    /** Generate + send the monthly student report to students and parents. */
    public const SEND_REPORT = 'send_report';

    /** Edit teacher master data (contact info, salary, specialisation, ...). */
    public const UPDATE_TEACHER = 'update_teacher';

    /**
     * action key => [
     *   label_en      human readable, English
     *   label_ar      human readable, Arabic
     *   permission    permission key that lets a user run (and review) the action directly
     *   group         grouping key used by the approvals UI filters
     *   executor      callable(PendingRequest $request, ?Model $actor): array|null
     * ]
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $actions = [];

    /**
     * Presentation + authorisation data for the built-in sensitive actions.
     * Executors are attached separately by PendingRequestServiceProvider.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            self::PAY_SALARY => [
                'label_en'   => 'Pay salary',
                'label_ar'   => 'صرف راتب',
                'permission' => 'salaries.approve',
                'group'      => 'finance',
            ],
            self::SEND_MARKS => [
                'label_en'   => 'Publish marks to parents',
                'label_ar'   => 'نشر الدرجات لأولياء الأمور',
                'permission' => 'paper_exams.approve',
                'group'      => 'academics',
            ],
            self::SEND_REPORT => [
                'label_en'   => 'Send monthly report',
                'label_ar'   => 'إرسال التقرير الشهري',
                'permission' => 'student_reports.approve',
                'group'      => 'academics',
            ],
            self::UPDATE_TEACHER => [
                'label_en'   => 'Edit teacher data',
                'label_ar'   => 'تعديل بيانات معلم',
                'permission' => 'teachers.approve',
                'group'      => 'teachers',
            ],
        ];
    }

    /**
     * Idempotent: seeds the built-in definitions so labels and permission keys
     * are always resolvable, even before the provider attaches executors.
     */
    protected static function ensureDefaults(): void
    {
        if (static::$seeded) {
            return;
        }

        static::$seeded = true;

        foreach (static::defaults() as $key => $definition) {
            static::define($key, $definition);
        }
    }

    /** @var bool */
    protected static bool $seeded = false;

    /**
     * Register (or extend) a sensitive action definition.
     */
    public static function define(string $key, array $definition): void
    {
        $existing = self::$actions[$key] ?? [];

        self::$actions[$key] = array_merge([
            'label_en' => $key,
            'label_ar' => $key,
            'permission' => 'approvals.sensitive',
            'group' => 'general',
            'executor' => null,
        ], $existing, $definition);
    }

    /**
     * Attach the callable that performs the action once an admin approves it.
     *
     * @param  callable(PendingRequest, ?Model): array  $executor
     */
    public static function executor(string $key, callable $executor): void
    {
        self::define($key, ['executor' => $executor]);
    }

    public static function has(?string $key): bool
    {
        static::ensureDefaults();

        return $key !== null && isset(self::$actions[$key]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(?string $key): ?array
    {
        static::ensureDefaults();

        if ($key === null || ! isset(self::$actions[$key])) {
            return null;
        }

        return self::$actions[$key];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        static::ensureDefaults();

        return self::$actions;
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(static::all());
    }

    /**
     * The permission that lets an actor approve (and run) this action.
     */
    public static function permissionFor(?string $key): string
    {
        $action = static::get($key);

        return (string) ($action['permission'] ?? 'approvals.sensitive');
    }

    /**
     * Every permission key that grants "run this action without approval".
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        $keys = [];

        foreach (static::all() as $action) {
            if (! empty($action['permission'])) {
                $keys[] = $action['permission'];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Localised label for an action, falling back to the stored request title
     * for actions that are not (or no longer) registered.
     */
    public static function label(?string $key, ?string $fallback = null, ?string $locale = null): string
    {
        $action = self::get($key);

        if (! $action) {
            return $fallback ?: (string) $key;
        }

        $locale = $locale ?: app()->getLocale();

        $label = $locale === 'ar'
            ? ($action['label_ar'] ?? $action['label_en'])
            : ($action['label_en'] ?? $action['label_ar']);

        return $label ?: ($fallback ?: (string) $key);
    }

    /**
     * Definitions shaped for the API so the dashboard and the approvals page
     * can render filters and badges without hardcoding the action list.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function catalogue(): array
    {
        $out = [];

        foreach (self::all() as $key => $action) {
            $out[] = [
                'key' => $key,
                'label_en' => $action['label_en'],
                'label_ar' => $action['label_ar'],
                'group' => $action['group'],
                'permission' => $action['permission'],
            ];
        }

        return $out;
    }

    /**
     * Run the executor of an approved request. Returns whatever the executor
     * produced so callers can bubble a domain conflict up as a 409.
     */
    public static function execute(string $key, \App\Models\PendingRequest $request, ?Model $actor): ?array
    {
        $executor = self::get($key)['executor'] ?? null;

        if (! is_callable($executor)) {
            return null;
        }

        return $executor($request, $actor);
    }

    /**
     * Drop every definition (tests).
     */
    public static function flush(): void
    {
        self::$actions = [];
        self::$seeded = false;
    }
}