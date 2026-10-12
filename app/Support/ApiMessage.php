<?php

namespace App\Support;

/**
 * Localises the human readable `message` / `errors` payload of an API response.
 *
 * The clients (dashboard and mobile app) declare the wanted language with the
 * `lang`, `X-Lang`, `X-Locale` or `Accept-Language` header (or `?lang=`), which
 * `App\Http\Middleware\SetApiLocale` turns into `app()->setLocale('ar'|'en')`.
 *
 * Controllers keep writing plain English sentences, so no call site has to change
 * to get Arabic output. Every outgoing message is resolved in this order:
 *
 *   1. exact   – full English sentence                 (resources/lang/ar/api.php exact)
 *   2. pattern – regex => Arabic template, where $1..$n are translated again
 *                through nouns/verbs/phrases
 *   3. phrases – needle => replacement, longest needle first
 *   4. the original English sentence (a response is never broken)
 *
 * Examples:
 *   "Announcement created successfully" -> "تم إنشاء الإعلان بنجاح"
 *   "Error retrieving students"          -> "خطأ أثناء استرجاع الطلاب"
 *   "Student not found"                  -> "الطالب غير موجود"
 */
class ApiMessage
{
    /** @var array<string, array<mixed>> Memoised language tables. */
    private static array $tables = [];

    /**
     * Translate one message. Unknown text is returned untouched.
     *
     * Anything the translator already localised itself (Laravel's own
     * validation.php output, custom trans() keys) is detected here and left
     * alone, so Arabic validation text is never re-processed by the English
     * sentence patterns below.
     */
    public static function translate(?string $message): ?string
    {
        if ($message === null || trim($message) === '') {
            return $message;
        }

        $locale = static::locale();

        // English is the source language: nothing to resolve.
        if ($locale === 'en') {
            return $message;
        }

        // Already localised (contains Arabic): Laravel resolved it for us.
        if (static::containsArabic($message)) {
            return $message;
        }

        // 1) Exact catalog entry.
        $exact = static::table('exact')[$message] ?? null;
        if (is_string($exact) && $exact !== '') {
            return $exact;
        }

        // 2) Sentence patterns, e.g. "<Noun> created successfully".
        foreach (static::table('patterns') as $pattern) {
            if (! is_array($pattern) || count($pattern) < 2 || ! is_string($pattern[0])) {
                continue;
            }

            $regex = $pattern[0];
            $template = (string) $pattern[1];

            if (! preg_match($regex, $message, $matches)) {
                continue;
            }

            // Translate every captured fragment, then splice it into the template
            // ourselves — preg_replace would re-read the groups from the English source.
            $groups = [];

            for ($i = 1, $max = count($matches); $i < $max; $i++) {
                $groups[(string) $i] = static::translateFragment($matches[$i]);
            }

            $translated = preg_replace_callback(
                '/\$(\d+)/',
                static fn (array $hit) => $groups[$hit[1]] ?? $hit[0],
                $template
            );

            if (is_string($translated) && $translated !== '' && $translated !== $message
                && static::isComplete($translated)) {
                    return $translated;
                }
            }

        // 3) Plain needle replacement. Only trust it when the source sentence had
        // no residual Latin words, otherwise we would emit a half-translated
        // sentence ("تم البريد الإلكتروني taken. بالفعل") that is worse than the
        // original English.
        foreach (static::phrases() as $needle => $replacement) {
            if ($needle !== '' && str_contains($message, $needle)) {
                $translated = str_replace($needle, $replacement, $message);

                if ($translated !== $message && static::isComplete($translated)) {
                    return $translated;
                }
            }
        }

        return $message;
    }

    /**
     * A partial translation still carrying Latin words (outside placeholders and
     * proper nouns) is not worth sending to the client.
     */
    private static function isComplete(string $value): bool
    {
        // Protect {$var}, :attribute, URLs and acronyms before scanning.
        $stripped = preg_replace('/\{\$[a-z_]+\}/u', '', $value) ?? $value;
        $stripped = preg_replace('/:[a-z_]+/u', '', $stripped) ?? $stripped;
        $stripped = preg_replace('#https?://\S+#u', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/\b[A-Z]{2,}\b/u', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/\b[a-z]+\d+\b/u', '', $stripped) ?? $stripped;

        return ! preg_match('/\b[a-z]{2,}\b/u', $stripped);
    }

    /**
     * Translate a noun / verb fragment captured by a pattern, so that
     * "Announcements" becomes "الإعلانات" inside the Arabic template.
     */
    public static function translateFragment(string $fragment): string
    {
        $fragment = trim($fragment);

        if ($fragment === '' || ! preg_match('/^[\x20-\x7E]+$/', $fragment)) {
            // Already Arabic (or mixed) — leave it alone.
            return $fragment;
        }

        // Trailing punctuation belongs to the sentence, not the noun.
        $trailing = '';
        if (preg_match('/^(.*?)([.,:;!?]+)$/', $fragment, $hit)) {
            $fragment = trim($hit[1]);
            $trailing = $hit[2];
        }

        if ($fragment === '') {
            return $trailing;
        }

        $translated = static::lookup($fragment);

        return $translated . $trailing;
    }

    /** Resolve a single word/phrase through exact -> nouns -> verbs -> actions. */
    private static function lookup(string $fragment): string
    {
        // A captured clause may itself be a catalogued phrase
        // ("perform this action", "this school").
        $exact = static::table('exact');
        if (array_key_exists($fragment, $exact)) {
            return $exact[$fragment];
        }

        // Leading quantifiers/articles: "All notifications" -> "جميع الإشعارات".
        foreach (['All ' => 'جميع ', 'The ' => '', 'this ' => '', 'This ' => ''] as $prefix => $arabic) {
            if (str_starts_with($fragment, $prefix)) {
                return $arabic.static::lookup(substr($fragment, strlen($prefix)));
            }
        }

        $nouns = static::table('nouns');
        $verbs = static::table('verbs');
        $actions = static::table('actions');

        // Longest key first: "processing fee" before "fee".
        if (array_key_exists($fragment, $nouns)) {
            return $nouns[$fragment];
        }

        if (array_key_exists($fragment, $verbs)) {
            return $verbs[$fragment];
        }

        if (array_key_exists($fragment, $actions)) {
            return $actions[$fragment];
        }

        $lower = strtolower($fragment);
        if (array_key_exists($lower, $nouns)) {
            return $nouns[$lower];
        }

        if (array_key_exists($lower, $verbs)) {
            return $verbs[$lower];
        }

        if (array_key_exists($lower, $actions)) {
            return $actions[$lower];
        }

        // "<noun> <noun>" combinations such as "fees invoices".
        $words = preg_split('/\s+/', $fragment) ?: [];
        $out = [];

        foreach ($words as $word) {
            $bare = strtolower(rtrim($word, '.,:;'));
            $out[] = $nouns[$bare] ?? $verbs[$bare] ?? $actions[$bare] ?? $nouns[$word] ?? $word;
        }

        $rebuilt = implode(' ', $out);

        return $rebuilt !== $fragment ? $rebuilt : $fragment;
    }

    /**
     * Translate a `field => string[]` validation map.
     *
     * @param  array<mixed>  $errors
     * @return array<mixed>
     */
    public static function translateErrors(array $errors): array
    {
        if (static::locale() === 'en') {
            return $errors;
        }

        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $errors[$field] = array_map(
                    static fn ($message) => is_string($message) ? static::translate($message) : $message,
                    $messages
                );
            } elseif (is_string($messages)) {
                $errors[$field] = static::translate($messages);
            }
        }

        return $errors;
    }

    /**
     * Translate the canonical envelope in place.
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    public static function translatePayload(array $payload): array
    {
        if (isset($payload['message']) && is_string($payload['message'])) {
            $payload['message'] = static::translate($payload['message']);
        }

        if (isset($payload['errors']) && is_array($payload['errors'])) {
            $payload['errors'] = static::translateErrors($payload['errors']);
        }

        // `error` carries technical detail (SQL/exception text) — only translate
        // it when it reads like prose, never touch driver noise.
        if (isset($payload['error']) && is_string($payload['error'])
            && str_contains($payload['error'], ' ') && ! preg_match('/[0-9]{4,}/', $payload['error'])) {
            $payload['error'] = static::translate($payload['error']);
        }

        return $payload;
    }

    /**
     * Translate a decoded JSON body of any shape.
     *
     * Only envelope keys are rewritten, and only inside a real response envelope
     * (an object carrying `success`). Some models have a user authored `message`
     * column (e.g. complaints), so recursing blindly would rewrite row data.
     * Envelopes nested inside `data` are still handled.
     *
     * @param  array<mixed>  $body
     * @return array<mixed>
     */
    public static function translateBody(array $body): array
    {
        $isEnvelope = array_key_exists('success', $body) || array_key_exists('errors', $body);

        foreach ($body as $key => $value) {
            if ($key === 'data' && is_array($value)) {
                $body[$key] = static::translateChildren($value);
            }
        }

        if (! $isEnvelope) {
            return $body;
        }

        if (isset($body['message']) && is_string($body['message'])) {
            $body['message'] = static::translate($body['message']);
        }

        if (isset($body['errors']) && is_array($body['errors'])) {
            $body['errors'] = static::translateErrors($body['errors']);
        }

        if (isset($body['error']) && is_string($body['error'])
            && str_contains($body['error'], ' ') && ! preg_match('/[0-9]{4,}/', $body['error'])) {
            $body['error'] = static::translate($body['error']);
        }

        return $body;
    }

    /**
     * Walk the `data` subtree, translating only what is itself an envelope.
     *
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private static function translateChildren(array $value): array
    {
        // A list of rows: leave every element alone.
        if (array_is_list($value)) {
            return $value;
        }

        return static::translateBody($value);
    }

    /** True when the string already carries Arabic script. */
    private static function containsArabic(string $value): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $value);
    }

    private static function locale(): string
    {
        $locale = app()->getLocale();

        return is_string($locale) && $locale !== '' ? $locale : 'en';
    }

    /**
     * @return array<string, mixed>
     */
    private static function table(string $key): array
    {
        $locale = static::locale();

        if (! isset(static::$tables[$locale])) {
            static::$tables[$locale] = [];
        }

        if (! array_key_exists($key, static::$tables[$locale])) {
            $loaded = trans('api.'.$key, [], $locale, 'en');
            static::$tables[$locale][$key] = is_array($loaded) ? $loaded : [];
        }

        return static::$tables[$locale][$key];
    }

    /**
     * Needle => replacement rules ordered so the longest needle wins.
     *
     * @return array<string, string>
     */
    private static function phrases(): array
    {
        $phrases = array_filter(static::table('phrases'), 'is_string');

        uksort($phrases, static fn ($a, $b) => mb_strlen((string) $b) <=> mb_strlen((string) $a));

        return $phrases;
    }

    /** Drop memoised tables (tests, long running workers). */
    public static function flush(): void
    {
        static::$tables = [];
    }
}