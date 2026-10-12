<?php

namespace App\Http\Controllers\Api\PendingRequests;

use App\Http\Controllers\Controller;
use App\Models\PendingRequest;
use App\Services\Pending\PendingActionRegistry;
use App\Services\Pending\PendingRequestPreviewService;
use App\Services\Pending\PendingRequestService;
use App\Services\Scoping\SchoolContext;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PendingRequestController extends Controller
{
    /**
     * List pending requests.
     *
     * Reviewers see their school's queue, everybody else sees only their own
     * submissions. `scope` narrows it further.
     */
    public function index(Request $request)
    {
        try {
            $actor = PendingRequestService::currentActor();

            if (! $actor) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $scope = $request->string('scope')->toString() ?: 'auto';

            if ($scope === 'reviewable' && ! PendingRequestService::isReviewer($actor)) {
                return ApiResponse::forbidden('You are not allowed to review requests.');
            }

            if ($scope === 'mine') {
                $query = PendingRequest::query()->with(['requester', 'reviewer'])->ofRequester($actor);
            } elseif ($scope === 'reviewable') {
                $query = PendingRequest::query()
                    ->with(['requester', 'reviewer'])
                    ->ofSchool(SchoolContext::schoolIdFrom($actor))
                    ->whereIn('action', PendingRequestService::reviewableActions($actor) ?? PendingActionRegistry::keys());
            } else {
                $query = PendingRequestService::visibleQuery($actor);
            }

            $query
                ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
                ->when($request->filled('action'), fn (Builder $q) => $q->where('action', $request->string('action')->toString()))
                ->when($request->filled('priority'), fn (Builder $q) => $q->where('priority', $request->string('priority')->toString()))
                ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")
                ->orderByDesc('id');

            $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
            $paginator = $query->paginate($perPage);

            // Present rows the same way `show()` does, so the queue and the widget
            // both receive action_label / requester / can_review. Returning raw
            // model arrays here left `can_review` undefined, which silently hid
            // the approve and reject controls on every list row.
            $paginator->through(function (PendingRequest $pending) use ($actor) {
                return PendingRequestService::present($pending) + [
                    'can_review' => PendingRequestService::canReview($actor, $pending),
                ];
            });

            return ApiResponse::paginate($paginator, 'Pending requests retrieved successfully');
        } catch (\Throwable $e) {
            return ApiResponse::fromException($e, 'Error retrieving pending requests');
        }
    }

    /**
     * Counters for the dashboard widget and the queue header.
     */
    public function stats(Request $request)
    {
        try {
            $actor = PendingRequestService::currentActor();

            if (! $actor) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            return ApiResponse::ok(PendingRequestService::stats($actor), 'Pending request statistics retrieved successfully');
        } catch (\Throwable $e) {
            return ApiResponse::fromException($e, 'Error retrieving pending request statistics');
        }
    }

    /**
     * The action catalogue (labels + owning permission) for the UI filters.
     */
    public function actions()
    {
        return ApiResponse::ok([
            'actions'      => PendingActionRegistry::catalogue(),
            'statuses'     => PendingRequest::statuses(),
            'priorities'   => PendingRequest::priorities(),
        ], 'Pending actions retrieved successfully');
    }

    public function show(Request $request, $id)
    {
        try {
            $actor = PendingRequestService::currentActor();

            if (! $actor) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $pending = PendingRequest::with(['requester', 'reviewer'])->find($id);

            if (! $pending) {
                return ApiResponse::notFound('Pending request not found.');
            }

            $isOwner = $pending->requested_by_id === $actor->getKey()
                && $pending->requested_by_type === get_class($actor);

            if (! $isOwner && ! PendingRequestService::canReview($actor, $pending)) {
                return ApiResponse::forbidden('You are not allowed to view this request.');
            }

            $data = PendingRequestService::present($pending);
            $data['can_review'] = PendingRequestService::canReview($actor, $pending);

            return ApiResponse::ok($data, 'Pending request retrieved successfully');
        } catch (\Throwable $e) {
            return ApiResponse::fromException($e, 'Error retrieving pending request');
        }
    }

    /**
     * Submit a request explicitly (used by screens that offer a
     * "request approval" button instead of performing the action).
     */
    public function store(Request $request)
    {
        try {
            $actor = PendingRequestService::currentActor();

            if (! $actor) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $data = ApiResponse::validate($request, [
                'action'      => 'required|string|max:60',
                'entity_type' => 'nullable|string|max:191',
                'entity_id'   => 'nullable|integer|min:1',
                'title'       => 'nullable|string|max:255',
                'title_ar'    => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'description_ar' => 'nullable|string',
                'reason'      => 'nullable|string',
                'priority'    => 'nullable|in:' . implode(',', PendingRequest::priorities()),
                'payload'     => 'nullable|array',
            ]);

            $action = $data['action'];

            if (! PendingActionRegistry::has($action)) {
                return ApiResponse::validation(
                    ['action' => ['This action cannot be requested for approval.']],
                    'Validation errors'
                );
            }

            // Nothing to gain from queuing an action the actor may already run.
            if (! PendingRequestService::requiresApproval($actor, $action)) {
                return ApiResponse::conflict('You are allowed to perform this action directly.');
            }

            $entity = null;

            if (! empty($data['entity_type']) && ! empty($data['entity_id']) && class_exists($data['entity_type'])) {
                $entity = $data['entity_type']::find($data['entity_id']);
            }

            $pending = PendingRequestService::defer($action, [
                'entity'        => $entity,
                'entity_type'   => $data['entity_type'] ?? ($entity ? get_class($entity) : null),
                'entity_id'     => $data['entity_id'] ?? null,
                'title'         => $data['title'] ?? null,
                'title_ar'      => $data['title_ar'] ?? null,
                'description'   => $data['description'] ?? null,
                'description_ar' => $data['description_ar'] ?? null,
                'payload'       => $data['payload'] ?? null,
                'reason'        => $data['reason'] ?? null,
                'priority'      => $data['priority'] ?? PendingRequest::PRIORITY_NORMAL,
            ], $actor);

            return ApiResponse::created(
                PendingRequestService::present($pending),
                'Pending request submitted successfully'
            );
        } catch (\Throwable $e) {
            return ApiResponse::fromException($e, 'Error submitting pending request');
        }
    }

    /**
     * What approving this request would actually do.
     *
     * Same authorisation as `show()` (owner or a reviewer for this action), so a
     * queued bulk action such as "send the monthly reports" can be inspected
     * record by record before anyone commits to it. Read-only by construction:
     * the preview service never writes.
     */
    public function preview(Request $request, $id)
    {
        try {
            $actor = PendingRequestService::currentActor();

            if (! $actor) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $pending = PendingRequest::find($id);

            if (! $pending) {
                return ApiResponse::notFound('Pending request not found.');
            }

            $isOwner = $pending->requested_by_id === $actor->getKey()
                && $pending->requested_by_type === get_class($actor);

            if (! $isOwner && ! PendingRequestService::canReview($actor, $pending)) {
                return ApiResponse::forbidden('You are not allowed to view this request.');
            }

            $preview = (new PendingRequestPreviewService())->build($pending, [
                'page'     => $request->integer('page', 1),
                'per_page' => $request->integer('per_page', PendingRequestPreviewService::DEFAULT_PER_PAGE),
                'q'        => $request->input('q'),
            ]);

            return ApiResponse::ok([
                'request' => PendingRequestService::present($pending),
                'preview' => $preview,
            ], 'Pending request preview retrieved successfully');
        } catch (\Throwable $e) {
            return ApiResponse::fromException($e, 'Error retrieving pending request preview');
        }
    }

    public function approve(Request $request, $id)
    {
        return $this->decide($request, $id, PendingRequest::STATUS_APPROVED);
    }

    public function reject(Request $request, $id)
    {
        return $this->decide($request, $id, PendingRequest::STATUS_REJECTED);
    }

    /**
     * Withdraw a request the caller submitted (while it is still pending).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $actor = PendingRequestService::currentActor();

            if (! $actor) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $pending = PendingRequest::find($id);

            if (! $pending) {
                return ApiResponse::notFound('Pending request not found.');
            }

            $isOwner = $pending->requested_by_id === $actor->getKey()
                && $pending->requested_by_type === get_class($actor);

            if (! $isOwner && ! PendingRequestService::canReview($actor, $pending)) {
                return ApiResponse::forbidden('You are not allowed to cancel this request.');
            }

            if (! $pending->isPending()) {
                return ApiResponse::conflict('This request has already been reviewed.');
            }

            $pending->update([
                'status'           => PendingRequest::STATUS_CANCELLED,
                'reviewed_by_id'   => $actor->getKey(),
                'reviewed_by_type' => get_class($actor),
                'reviewed_at'      => now(),
                'review_notes'     => $request->input('notes'),
            ]);

            return ApiResponse::done('Pending request cancelled successfully');
        } catch (\Throwable $e) {
            return ApiResponse::fromException($e, 'Error cancelling pending request');
        }
    }

    /**
     * Shared approve / reject handler.
     */
    private function decide(Request $request, $id, string $decision)
    {
        try {
            $actor = PendingRequestService::currentActor();

            if (! $actor) {
                return ApiResponse::error('Unauthenticated.', 401);
            }

            $pending = PendingRequest::find($id);

            if (! $pending) {
                return ApiResponse::notFound('Pending request not found.');
            }

            $notes = $request->input('notes');

            if ($decision === PendingRequest::STATUS_REJECTED && ! $notes) {
                return ApiResponse::validation(
                    ['notes' => ['A reason is required when rejecting a request.']],
                    'Validation errors'
                );
            }

            return PendingRequestService::review($pending, $decision, $actor, $notes);
        } catch (\Throwable $e) {
            return ApiResponse::fromException($e, 'Error reviewing pending request');
        }
    }
}