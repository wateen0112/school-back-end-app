<?php

namespace App\Http\Middleware;

use App\Support\ApiMessage;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Localises the `message` / `errors` text of every API response.
 *
 * Why a middleware instead of only touching App\Response\ApiResponse: the vast
 * majority of the endpoints answer with a plain `response()->json([...])`
 * envelope, so translating at the boundary is what actually guarantees the
 * dashboard and the mobile app receive Arabic (or English) text without touching
 * hundreds of controllers.
 *
 * Must run after SetApiLocale (which is prepended to the api group) so the
 * request locale is already resolved.
 */
class LocalizeApiResponse
{
    /**
     * Only JSON payloads are rewritten. Compare the content-type subtype so both
     * "application/json" and "application/problem+json" are covered regardless of
     * any charset parameter.
     */
    private const JSON_SUBTYPES = ['json', 'problem+json', 'vnd.api+json'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (app()->getLocale() === 'en' || ! $response instanceof JsonResponse) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        if (! in_array(static::jsonType($contentType), self::JSON_SUBTYPES, true)) {
            return $response;
        }

        $decoded = json_decode((string) $response->getContent(), true);

        if (! is_array($decoded)) {
            return $response;
        }

        $translated = ApiMessage::translateBody($decoded);

        if ($translated === $decoded) {
            return $response;
        }

        $new = new JsonResponse($translated, $response->getStatusCode());

        foreach ($response->headers->all() as $name => $values) {
            if (in_array(strtolower($name), ['content-type', 'content-length'], true)) {
                continue;
            }

            $new->headers->set($name, $values, true);
        }

        $new->headers->set('Content-Language', app()->getLocale());

        return $new;
    }

    /** "application/json; charset=UTF-8" -> "json" */
    private static function jsonType(string $contentType): string
    {
        $type = strtolower(trim(explode(';', $contentType)[0]));
        $slash = strrpos($type, '/');

        return $slash === false ? $type : substr($type, $slash + 1);
    }
}
