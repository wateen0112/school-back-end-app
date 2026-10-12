<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cheap stamp-based cache invalidation.
 *
 * Read endpoints embed `stamp($group)` in their cache key; write endpoints
 * call `bump($group)` to invalidate every cached read under that group at
 * once, avoiding fragile per-key bookkeeping.
 */
class CacheVersion
{
    public static function stamp(string $group): int
    {
        return (int) Cache::remember("cache_version:{$group}", 86400, fn () => 1);
    }

    public static function bump(string $group): void
    {
        Cache::increment("cache_version:{$group}");
    }
}