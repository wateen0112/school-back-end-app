<?php

namespace App\Support;

use App\Support\ApiMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Canonical API response contract.
 *
 * Every endpoint must answer with exactly this envelope so the frontend can
 * parse one shape:
 *
 *   2xx  { success: true,  message: string, data?: mixed, meta?: object }
 *   4xx  { success: false, message: string, error?: string, errors?: object, meta?: object }
 *
 * Rules:
 *   - `message` is ALWAYS present and human readable (safe to show to the user).
 *   - `error`   is optional technical detail (exception message / SQL reason).
 *   - `errors`  is a field => string[] map and appears ONLY on HTTP 422,
 *               which is reserved exclusively for request validation.
 *   - Business-rule conflicts (already paid, duplicate slot, published scenario)
 *     use HTTP 409 with a plain `message`, never 422.
 *   - HTTP status meaning: 400 malformed, 401 unauthenticated, 403 forbidden,
 *     404 missing, 409 conflict, 422 validation, 429 rate limited, 5xx server.
 */
class ApiResponse
{
    public static function ok(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'message' => ApiMessage::translate($message ?? 'Request completed successfully')];

        if ($data !== null) {
            $payload['data'] = self::normalize($data);
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return self::ok($data, $message, [], 201);
    }

    /** A successful delete/update that has nothing meaningful to return. */
    public static function done(?string $message = null): JsonResponse
    {
        return self::ok(null, $message ?? 'Operation completed successfully');
    }

    public static function error(string $message, int $status = 400, ?string $error = null, array $meta = []): JsonResponse
    {
        // Translated here as well as in the middleware so the helper stays correct
        // when it is used outside the api middleware group (tests, console, mail).
        $payload = ['success' => false, 'message' => ApiMessage::translate($message)];

        if ($error !== null && $error !== '') {
            $payload['error'] = $error;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function notFound(string $message = 'Resource not found.', ?string $error = null): JsonResponse
    {
        return self::error($message, 404, $error);
    }

    public static function forbidden(string $message = 'This action is not allowed.', ?string $error = null): JsonResponse
    {
        return self::error($message, 403, $error);
    }

    /**
     * A request could be understood but conflicts with current state
     * ("already paid", "slot is taken", "scenario is published").
     */
    public static function conflict(string $message, ?string $error = null): JsonResponse
    {
        return self::error($message, 409, $error);
    }

    public static function serverError(string $message = 'Server error.', ?string $error = null): JsonResponse
    {
        return self::error($message, 500, $error);
    }

    /** HTTP 422 — field level validation. Always carries an `errors` map. */
    public static function validation(array $errors, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => ApiMessage::translate($message ?? 'Validation errors'),
            'errors' => ApiMessage::translateErrors(self::normalizeErrors($errors)),
        ], 422);
    }

    /** Build the 422 response straight from a validator. */
    public static function fromValidator($validator, ?string $message = null): JsonResponse
    {
        return self::validation($validator->errors()->toArray(), $message);
    }

    /**
     * Convenience for the common `$validator->fails()` pattern:
     * returns null when valid, or a ready-to-return 422 response.
     */
    public static function check($validator, ?string $message = null): ?JsonResponse
    {
        return $validator->fails() ? self::validation($validator->errors()->toArray(), $message) : null;
    }

    /**
     * Validate, or throw so the global handler renders the canonical 422.
     * Returns the validated data so callers can use it directly.
     */
    public static function validate(\Illuminate\Http\Request $request, array $rules, array $messages = []): array
    {
        return Validator::make($request->all(), $rules, $messages)->validate();
    }

    /** Laravel paginator => `data` array + `meta` (current_page, last_page, ...). */
    public static function paginate($paginator, ?string $message = null, string $collectionKey = 'data'): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => ApiMessage::translate($message ?? 'Records retrieved successfully'),
        ];

        if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $payload['data'] = $paginator->items();
            $payload['meta'] = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ];
        } else {
            $payload['data'] = self::normalize($paginator);
        }

        unset($collectionKey);

        return response()->json($payload, 200);
    }

    /**
     * Map a caught exception onto the contract so a `catch (\Exception $e)` block
     * cannot turn a missing record into a 500.
     *
     *   catch (\Throwable $e) {
     *       return ApiResponse::fromException($e, 'Error paying salary');
     *   }
     */
    public static function fromException(\Throwable $e, string $message = 'Server error.'): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return self::validation($e->errors());
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            || $e instanceof \Illuminate\Database\QueryException && self::isMissingRecord($e)) {
            return self::notFound('The requested resource was not found.');
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return self::error('Unauthenticated.', 401);
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException
            || $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return self::forbidden('This action is not allowed.');
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return self::notFound('The requested resource was not found.');
        }

        if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
            return self::error('Too many requests, please try again later.', 429);
        }

        // A domain conflict raised as an exception (salary already paid, exam
        // already published, ...): the documented answer is 409, never a 500.
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\ConflictHttpException) {
            return self::conflict($e->getMessage() ?: 'The action conflicts with the current data.');
        }

        return self::serverError($message, $e->getMessage());
    }

    private static function isMissingRecord(\Illuminate\Database\QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');

        return in_array($sqlState, ['42S02', '23000'], true);
    }

    private static function normalize(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        if ($data instanceof \Illuminate\Contracts\Support\Arrayable) {
            return $data->toArray();
        }

        return $data;
    }

    /** Guarantee `field => string[]` regardless of the incoming error shape. */
    private static function normalizeErrors(array $errors): array
    {
        $out = [];

        foreach ($errors as $field => $messages) {
            $out[$field] = is_array($messages) ? array_values($messages) : [(string) $messages];
        }

        return $out;
    }
}