<?php

namespace App\Services\Pending;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\PendingRequest;
use App\Services\AuditLogger;
use App\Services\FcmNotificationService;
use App\Services\Scoping\SchoolContext;
use App\Support\ApiResponse;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Approval workflow for "sensitive actions".
 *
 * Paying a salary, publishing marks to parents, sending a monthly report or
 * editing teacher data cannot be performed directly by an unprivileged user.
 * Instead of failing, the controller hands the intent over to `defer()`:
 *
 *   1. a `pending_requests` row is created (deduplicated per action+entity),
 *   2. every admin who may review that action is notified in-app + by FCM,
 *   3. an approver calls `review()`, which runs the registered executor,
 *   4. the requester is notified with the decision.
 */
class PendingRequestService
{
    /** Permission that lets a staff member review every sensitive action. */
    public const REVIEW_ALL = 'approvals.sensitive';

    /**
     * Permission that lets a staff member open the pending requests queue.
     *
     * It grants access to the screen and its counters, never a decision: approving needs
     * `REVIEW_ALL` or the action's own `<module>.approve` key.
     */
    public const MANAGE = 'pending_requests.manage';

    // ------------------------------------------------------------------
    // Authorisation
    // ------------------------------------------------------------------

    /**
     * May the actor perform this action right now, without approval?
     *
     * Unregistered actions are never gated, so adding a new sensitive action
     * never silently breaks the endpoints that do not declare it.
     */
    public static function canRunDirectly(?Model $actor, string $action): bool
    {
        if (! $actor) {
            return false;
        }

        $definition = PendingActionRegistry::get($action);

        if (! $definition) {
            return true;
        }

        if (! $actor instanceof User) {
            // Teachers / students / parents never hold approval permissions.
            return false;
        }

        if ($actor->is_super_admin) {
            return true;
        }

        return static::holdsReviewPermission($actor, $action);
    }

    public static function requiresApproval(?Model $actor, string $action): bool
    {
        return ! static::canRunDirectly($actor, $action);
    }

    /**
     * True when the actor can decide on this specific request.
     */
    public static function canReview(?Model $actor, PendingRequest $request): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        // Self-approval is never allowed, not even for a super admin: the point
        // of the queue is that somebody else signs the work off.
        if ($request->requested_by_type === User::class
            && (int) $request->requested_by_id === (int) $actor->getKey()) {
            return false;
        }

        // Tenancy: holding the permission is not enough, the request has to
        // belong to the reviewer's school. Platform admins (no school) are the
        // only ones who may cross school lines.
        $schoolId = SchoolContext::schoolIdFrom($actor);

        if ($schoolId !== null && (int) $request->school_id !== $schoolId) {
            return false;
        }

        if ($actor->is_super_admin) {
            return true;
        }

        // Only an approval permission authorises a decision. `pending_requests.manage`
        // deliberately does NOT appear here: it opens the queue screen, while
        // `approvals.sensitive` is the single key that approves everything. Letting the
        // queue manager decide would silently make the per-module keys meaningless.
        return static::holdsReviewPermission($actor, (string) $request->action);
    }

    /**
     * Can the actor open the queue at all (review something, or manage it)?
     */
    public static function canAccessQueue(?Model $actor): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        if ($actor->is_super_admin) {
            return true;
        }

        if ($actor->hasPermission(self::MANAGE) || $actor->hasPermission(self::REVIEW_ALL)) {
            return true;
        }

        foreach (PendingActionRegistry::permissions() as $permission) {
            if ($actor->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the actor an approver for at least one sensitive action?
     */
    public static function isReviewer(?Model $actor): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        if ($actor->is_super_admin || $actor->hasPermission(self::REVIEW_ALL)) {
            return true;
        }

        foreach (PendingActionRegistry::permissions() as $permission) {
            if ($actor->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private static function holdsReviewPermission(User $actor, string $action): bool
    {
        if ($actor->hasPermission(self::REVIEW_ALL)) {
            return true;
        }

        $permission = PendingActionRegistry::get($action)['permission'] ?? null;

        return $permission !== null && $actor->hasPermission($permission);
    }

    // ------------------------------------------------------------------
    // Deferring a sensitive action
    // ------------------------------------------------------------------

    /**
     * Record the intent instead of executing it, and alert the approvers.
     *
     * Returns the (possibly pre-existing) pending request so the caller can
     * answer with its id.
     *
     * @param  array{entity?: Model|null, title?: string, title_ar?: string,
     *               description?: string|null, description_ar?: string|null,
     *               payload?: array|null, reason?: string|null,
     *               priority?: string|null, method?: string|null,
     *               endpoint?: string|null}  $options
     */
    public static function defer(string $action, array $options = [], ?Model $actor = null): PendingRequest
    {
        $actor ??= static::currentActor();
        $entity = $options['entity'] ?? null;

        $request = static::existingPending($action, $entity, $actor);

        if ($request) {
            return $request;
        }

        $schoolId = SchoolContext::schoolIdFrom($actor)
            ?? ($entity?->getAttribute('school_id'))
            ?? $actor?->getAttribute('school_id');

        // The queue is per school: without a tenant there is no approver who
        // could ever see the request, so refuse instead of writing a row that
        // nobody can review (and that the NOT NULL column would reject anyway).
        if ($schoolId === null) {
            throw new ConflictHttpException('This action cannot be queued for approval: your account is not linked to a school.');
        }

        $request = PendingRequest::create([
            'school_id'          => $schoolId,
            'action'             => $action,
            'entity_type'        => $entity ? get_class($entity) : ($options['entity_type'] ?? null),
            'entity_id'          => $entity?->getKey() ?? ($options['entity_id'] ?? null),
            'title'              => $options['title'] ?? PendingActionRegistry::label($action, null, 'en'),
            'title_ar'           => $options['title_ar'] ?? PendingActionRegistry::label($action, null, 'ar'),
            'description'        => $options['description'] ?? null,
            'description_ar'     => $options['description_ar'] ?? null,
            'payload'            => $options['payload'] ?? null,
            'reason'             => $options['reason'] ?? null,
            'status'             => PendingRequest::STATUS_PENDING,
            'priority'           => $options['priority'] ?? PendingRequest::PRIORITY_NORMAL,
            'requested_by_id'    => $actor?->getKey(),
            'requested_by_type'  => $actor ? get_class($actor) : User::class,
            // Kept for traceability: tells the approver where the action was asked for.
            'review_notes'       => null,
        ]);

        if (! empty($options['endpoint'])) {
            $request->payload = array_merge($request->payload ?? [], ['_endpoint' => $options['endpoint']]);
            $request->save();
        }

        static::notifyApprovers($request);

        AuditLogger::log('pending_request.created', PendingRequest::class, $request->getKey(), $request->title, [
            'action' => $action,
            'school_id' => $schoolId,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
        ]);

        return $request;
    }

    /**
     * A pending row that already covers the same action on the same record by
     * the same requester: re-asking should not flood the approvers' inbox.
     */
    protected static function existingPending(string $action, ?Model $entity, ?Model $actor): ?PendingRequest
    {
        if (! $actor) {
            return null;
        }

        $query = PendingRequest::pending()
            ->where('action', $action)
            ->ofRequester($actor)
            ->orderByDesc('id');

        if ($entity) {
            $query->where('entity_type', get_class($entity))
                ->where('entity_id', $entity->getKey());
        }

        return $query->first();
    }

    /**
     * The canonical answer for "this action needs approval": HTTP 202 with the
     * created pending request.
     */
    public static function deferredResponse(PendingRequest $request): JsonResponse
    {
        return ApiResponse::ok([
            'pending_request' => static::present($request),
            'requires_approval' => true,
        ], 'This action requires approval. A pending request has been sent to the administrators.', [
            'pending_request_id' => $request->getKey(),
        ], 202);
    }

    // ------------------------------------------------------------------
    // Reviewing
    // ------------------------------------------------------------------

    /**
     * Approve or reject a pending request.
     *
     * On approval the registered executor runs inside a transaction; a domain
     * conflict is reported through the returned JsonResponse (409) and leaves
     * the request pending so the approver can retry.
     */
    public static function review(PendingRequest $request, string $decision, ?Model $actor = null, ?string $notes = null): JsonResponse
    {
        $actor ??= static::currentActor();

        if ($request->status !== PendingRequest::STATUS_PENDING) {
            return ApiResponse::conflict('This request has already been reviewed.');
        }

        if (! static::canReview($actor, $request)) {
            return ApiResponse::forbidden('You are not allowed to review this request.');
        }

        $decision = $decision === PendingRequest::STATUS_APPROVED
            ? PendingRequest::STATUS_APPROVED
            : PendingRequest::STATUS_REJECTED;

        if ($decision === PendingRequest::STATUS_APPROVED) {
            $result = static::apply($request, $actor);

            if ($result !== null) {
                return $result;
            }
        }

        $request->update([
            'status'           => $decision,
            'reviewed_by_id'   => $actor?->getKey(),
            'reviewed_by_type' => $actor ? get_class($actor) : null,
            'reviewed_at'      => now(),
            'review_notes'     => $notes,
        ]);

        static::notifyRequester($request, $decision);
        static::touchQueueCaches();

        AuditLogger::log('pending_request.'.$decision, PendingRequest::class, $request->getKey(), $request->title, [
            'action' => $request->action,
            'reviewer_id' => $actor?->getKey(),
        ]);

        return ApiResponse::ok(
            static::present($request->fresh()),
            $decision === PendingRequest::STATUS_APPROVED
                ? 'Request approved successfully'
                : 'Request rejected successfully'
        );
    }

    /**
     * Run the executor for an approved request.
     *
     * Returns a JsonResponse when the action could not be completed (so the
     * request stays pending), or null when it succeeded.
     */
    protected static function apply(PendingRequest $request, ?Model $actor): ?JsonResponse
    {
        if (! PendingActionRegistry::has($request->action)) {
            return ApiResponse::serverError('This action cannot be executed: no handler is registered.');
        }

        try {
            DB::beginTransaction();

            PendingActionRegistry::execute($request->action, $request, $actor);

            DB::commit();
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            $request->forceFill(['apply_error' => $e->getMessage()])->save();

            return ApiResponse::validation($e->errors(), 'The requested action is no longer valid.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $request->forceFill(['apply_error' => $e->getMessage()])->save();

            Log::warning('Pending request executor failed: '.$e->getMessage(), [
                'pending_request_id' => $request->getKey(),
                'action' => $request->action,
            ]);

            // A business rule conflict (already paid, published, ...) is a 409 and
            // keeps the request open; anything else is a genuine server error.
            $status = $e instanceof \Symfony\Component\HttpKernel\Exception\ConflictHttpException ? 409 : 500;

            return $status === 409
                ? ApiResponse::conflict('The requested action conflicts with the current data.', $e->getMessage())
                : ApiResponse::fromException($e, 'Error executing the requested action');
        }

        $request->forceFill([
            'applied_at' => now(),
            'apply_error' => null,
        ])->save();

        return null;
    }

    // ------------------------------------------------------------------
    // Querying
    // ------------------------------------------------------------------

    /**
     * The actions this actor may approve, or null when they may approve anything
     * (super admin / blanket reviewer).
     *
     * This is the SQL-level twin of canReview(): approval rights live on roles,
     * so the set of actions is derived from the registry and applied as a
     * `whereIn` filter. That keeps pagination totals honest, which a per-row
     * filter after paginating cannot do.
     *
     * @return array<int, string>|null
     */
    public static function reviewableActions(?Model $actor): ?array
    {
        if (! $actor instanceof User) {
            return [];
        }

        if ($actor->is_super_admin || $actor->hasPermission(self::REVIEW_ALL)) {
            return null;
        }

        return array_values(array_filter(
            PendingActionRegistry::keys(),
            fn (string $key) => $actor->hasPermission(PendingActionRegistry::permissionFor($key))
        ));
    }

    /**
     * Requests the actor is allowed to see: reviewers see the school queue,
     * everybody else only their own submissions.
     */
    public static function visibleQuery(?Model $actor): Builder
    {
        $query = PendingRequest::query()->with(['requester', 'reviewer']);

        $actions = static::reviewableActions($actor);

        if ($actions !== null) {
            // Not a reviewer: own submissions only.
            if ($actions === []) {
                return $query->ofRequester($actor);
            }

            // Partial reviewer: own submissions plus the actions they can approve.
            $query->where(function (Builder $q) use ($actor, $actions) {
                $q->ofRequester($actor)->orWhereIn('action', $actions);
            });
        }

        SchoolContext::scope($query, SchoolContext::schoolIdFrom($actor));

        return $query;
    }

    /**
     * Requests the actor is allowed to decide on.
     *
     * Approval rights are held on roles, not rows, so the school queue is
     * narrowed with canReview() instead of a SQL permission predicate.
     *
     * @return \Illuminate\Support\Collection<int, PendingRequest>
     */
    public static function reviewable(?Model $actor, ?string $status = PendingRequest::STATUS_PENDING)
    {
        $schoolId = SchoolContext::schoolIdFrom($actor);

        return PendingRequest::query()
            ->with(['requester', 'reviewer'])
            ->ofSchool($schoolId)
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->get()
            ->filter(fn (PendingRequest $request) => static::canReview($actor, $request))
            ->values();
    }

    /**
     * Badge counters for the dashboard widget and the queue header.
     */
    public static function stats(?Model $actor): array
    {
        $reviewable = static::reviewable($actor, PendingRequest::STATUS_PENDING);
        $history = static::reviewable($actor, null);

        return [
            'pending'   => $reviewable->count(),
            'approved'  => $history->where('status', PendingRequest::STATUS_APPROVED)->count(),
            'rejected'  => $history->where('status', PendingRequest::STATUS_REJECTED)->count(),
            'mine'      => PendingRequest::ofRequester($actor)->where('status', PendingRequest::STATUS_PENDING)->count(),
            'by_action' => $reviewable
                ->groupBy('action')
                ->map(fn ($group) => $group->count())
                ->all(),
        ];
    }

    // ------------------------------------------------------------------
    // Presentation
    // ------------------------------------------------------------------

    public static function present(PendingRequest $request): array
    {
        $requester = $request->requester;

        return [
            'id'            => $request->id,
            'action'        => $request->action,
            'action_label'  => $request->actionLabel(),
            'title'         => $request->localizedTitle(),
            'title_en'      => $request->title,
            'title_ar'      => $request->title_ar,
            'description'   => $request->localizedDescription(),
            'description_en' => $request->description,
            'description_ar' => $request->description_ar,
            'payload'       => $request->payload,
            'reason'        => $request->reason,
            'status'        => $request->status,
            'priority'      => $request->priority,
            'entity_type'   => $request->entity_type,
            'entity_id'     => $request->entity_id,
            'school_id'     => $request->school_id,
            'requester'     => static::presentActor($requester, $request->requested_by_type, $request->requested_by_id),
            'requester_role' => static::roleOf($request->requested_by_type),
            'reviewer'      => static::presentActor($request->reviewer, $request->reviewed_by_type, $request->reviewed_by_id),
            'reviewed_at'   => $request->reviewed_at,
            'review_notes'  => $request->review_notes,
            'applied_at'    => $request->applied_at,
            'apply_error'   => $request->apply_error,
            'created_at'    => $request->created_at,
            'updated_at'    => $request->updated_at,
        ];
    }

    protected static function presentActor(?Model $actor, ?string $type, $id): ?array
    {
        if (! $id) {
            return null;
        }

        $name = null;
        $email = null;

        if ($actor) {
            $email = $actor->getAttribute('email');
            $name = static::displayName($actor);
        }

        return [
            'id'    => $id,
            'type'  => $type,
            'role'  => static::roleOf($type),
            'name'  => $name,
            'email' => $email,
        ];
    }

    protected static function displayName(Model $actor): ?string
    {
        foreach (['name', 'Name', 'Name_Father', 'full_name'] as $key) {
            $value = $actor->getAttribute($key);

            if (is_array($value)) {
                return $value['ar'] ?? $value['en'] ?? null;
            }

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $actor->getAttribute('email');
    }

    public static function roleOf(?string $type): string
    {
        if (! $type) {
            return 'unknown';
        }

        if (str_contains($type, 'Teacher')) {
            return 'teacher';
        }

        if (str_contains($type, 'Student')) {
            return 'student';
        }

        if (str_contains($type, 'Parent')) {
            return 'parent';
        }

        return 'admin';
    }

    // ------------------------------------------------------------------
    // Notifications
    // ------------------------------------------------------------------

    /**
     * Alert every admin of the school who may review this action.
     */
    public static function notifyApprovers(PendingRequest $request): void
    {
        try {
            $recipients = static::approverCandidates($request);

            if ($recipients->isEmpty()) {
                return;
            }

            $definition = PendingActionRegistry::get($request->action);
            $label = PendingActionRegistry::label($request->action, $request->title);
            $requesterName = static::displayName($request->requester) ?? __('Unknown');

            $notification = Notification::create([
                'title' => app()->getLocale() === 'ar'
                    ? 'طلب جديد بانتظار الموافقة'
                    : 'New request awaiting approval',
                'body' => app()->getLocale() === 'ar'
                    ? $requesterName.' يطلب: '.$label
                    : $requesterName.' requested: '.$label,
                'type' => 'targeted',
                'sender_id' => $request->requested_by_id,
                'sender_type' => $request->requested_by_type,
                'target_role' => null,
                'data' => [
                    'type' => 'pending_request_created',
                    'pending_request_id' => $request->id,
                    'action' => $request->action,
                    'permission' => $definition['permission'] ?? null,
                ],
            ]);

            $now = now();
            $rows = [];

            foreach ($recipients as $admin) {
                $rows[] = [
                    'notification_id' => $notification->id,
                    'recipient_id'    => $admin->getKey(),
                    'recipient_type'  => User::class,
                    'is_read'         => false,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                NotificationRecipient::insert($chunk);
            }

            $fcm = new FcmNotificationService();
            $fcm->sendToUsers(
                $recipients->map(fn ($admin) => ['id' => $admin->getKey(), 'type' => User::class])->all(),
                $notification->title,
                $notification->body,
                [
                    'type' => 'pending_request_created',
                    'pending_request_id' => $request->id,
                    'action' => $request->action,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Pending request approver notification failed: '.$e->getMessage());
        }
    }

    /**
     * Tell the requester how their request ended.
     */
    public static function notifyRequester(PendingRequest $request, string $decision): void
    {
        try {
            if (! $request->requested_by_id || ! $request->requested_by_type) {
                return;
            }

            $class = $request->requested_by_type;

            if (! class_exists($class)) {
                return;
            }

            $recipient = $class::find($request->requested_by_id);

            if (! $recipient) {
                return;
            }

            $label = PendingActionRegistry::label($request->action, $request->title);
            $approved = $decision === PendingRequest::STATUS_APPROVED;

            $title = $approved
                ? (app()->getLocale() === 'ar' ? 'تمت الموافقة على طلبك' : 'Your request was approved')
                : (app()->getLocale() === 'ar' ? 'تم رفض طلبك' : 'Your request was rejected');

            $body = $approved
                ? (app()->getLocale() === 'ar' ? 'تم تنفيذ: '.$label : 'Executed: '.$label)
                : (app()->getLocale() === 'ar' ? 'رُفض: '.$label : 'Rejected: '.$label);

            $notification = Notification::create([
                'title' => $title,
                'body' => $body,
                'type' => 'targeted',
                'sender_id' => $request->reviewed_by_id,
                'sender_type' => $request->reviewed_by_type,
                'target_role' => null,
                'data' => [
                    'type' => 'pending_request_'.$decision,
                    'pending_request_id' => $request->id,
                    'action' => $request->action,
                    'status' => $decision,
                ],
            ]);

            NotificationRecipient::create([
                'notification_id' => $notification->id,
                'recipient_id'    => $recipient->getKey(),
                'recipient_type'  => $class,
                'is_read'         => false,
            ]);

            $fcm = new FcmNotificationService();
            $fcm->sendToUsers([['id' => $recipient->getKey(), 'type' => $class]], $title, $body, [
                'type' => 'pending_request_'.$decision,
                'pending_request_id' => $request->id,
                'action' => $request->action,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Pending request requester notification failed: '.$e->getMessage());
        }
    }

    /**
     * Users who may be alerted about this request: the school's admins who
     * hold the action's approval permission (or the blanket reviewer role).
     *
     * Approval rights live on roles, so this cannot be a SQL predicate; the
     * school filter keeps the candidate set small.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected static function approverCandidates(PendingRequest $request): Collection
    {
        $permission = PendingActionRegistry::permissionFor($request->action);

        return User::query()
            ->when($request->school_id, fn ($q) => $q->where('school_id', $request->school_id))
            ->get()
            ->filter(fn (User $admin) => $admin->is_super_admin
                || $admin->hasPermission(self::REVIEW_ALL)
                || $admin->hasPermission($permission))
            ->values();
    }

    protected static function touchQueueCaches(): void
    {
        if (class_exists(\App\Support\CacheVersion::class)) {
            \App\Support\CacheVersion::bump('pending_requests');
        }
    }

    /**
     * The acting user across every guard this app authenticates with.
     */
    public static function currentActor(): ?Model
    {
        $user = auth()->user()
            ?? auth('api')->user()
            ?? auth('web')->user()
            ?? auth('teacher')->user()
            ?? auth('student')->user()
            ?? auth('parent')->user();

        return $user instanceof Model ? $user : null;
    }
}