<?php

namespace App\Services\Transportation;

use App\Models\Transportation\TransportationSetting;
use App\Services\Transportation\Contracts\MapProvider;
use App\Services\Transportation\Providers\NominatimProvider;

/**
 * Resolves the configured map provider for a school.
 * Adding Google Maps / Mapbox later = one new MapProvider class + config value.
 */
class MapService
{
    /** @var array<string, MapProvider> */
    protected static array $providers = [];

    public static function register(MapProvider $provider): void
    {
        static::$providers[$provider->key()] = $provider;
    }

    public static function provider(?string $key = null): MapProvider
    {
        $key = $key ?: 'osm';

        if (! isset(static::$providers[$key])) {
            // Default fallback keeps the module functional even for unknown keys.
            static::$providers[$key] = new NominatimProvider();
        }

        return static::$providers[$key];
    }

    public static function forSchool(int $schoolId): MapProvider
    {
        $setting = TransportationSetting::forSchool($schoolId);

        return static::provider($setting->map_provider);
    }
}
