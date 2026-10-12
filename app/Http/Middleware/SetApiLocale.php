<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Resolves the response language (ar/en) for the whole API surface.
 *
 * Precedence, first match wins:
 *   1. ?locale=  2. ?lang=  3. X-Lang  4. lang  5. X-Locale  6. Accept-Language
 *
 * `Accept-Language` style values are normalised, so all of these resolve to "ar":
 *   ar, ar-EG, ar-SA, ar-EG,ar;q=0.9, AR, arabic
 * and to "en": en, en-US, en-GB, english
 *
 * Anything unknown is ignored and the configured app.locale is kept, so a bad
 * header can never break a response.
 */
class SetApiLocale
{
    /** Locales the API is translated into. */
    private const SUPPORTED = ['ar', 'en'];

    /** Fallback when a header carries a region/script we do not know (e.g. "ar-EG" -> "ar"). */
    private const ALIASES = [
        'arabic' => 'ar',
        'english' => 'en',
        'en-us' => 'en',
        'en-gb' => 'en',
        'ar-sa' => 'ar',
        'ar-eg' => 'ar',
        'ar-ae' => 'ar',
        'ar-ps' => 'ar',
    ];

    public function handle(Request $request, Closure $next)
    {
        $locale = $this->resolve($request);

        if ($locale !== null) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function resolve(Request $request): ?string
    {
        $candidates = [
            $request->query('locale'),
            $request->query('lang'),
            $request->header('X-Lang'),
            $request->header('lang'),
            $request->header('X-Locale'),
            $request->header('X-Language'),
            $request->header('Accept-Language'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            // "ar-EG,ar;q=0.9,en;q=0.8" -> try each entry in order.
            foreach (explode(',', $candidate) as $entry) {
                $tag = strtolower(trim(explode(';', $entry)[0]));

                if ($tag === '') {
                    continue;
                }

                $match = $this->match($tag);

                if ($match !== null) {
                    return $match;
                }
            }
        }

        return null;
    }

    private function match(string $tag): ?string
    {
        if (in_array($tag, self::SUPPORTED, true)) {
            return $tag;
        }

        if (isset(self::ALIASES[$tag])) {
            return self::ALIASES[$tag];
        }

        // "ar-EG" / "ar_EG" / "ar.eg" -> "ar".
        $primary = preg_split('/[-_.]/', $tag)[0] ?? '';

        return in_array($primary, self::SUPPORTED, true) ? $primary : null;
    }
}