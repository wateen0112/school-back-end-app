<?php

namespace App\Services\Transportation\Providers;

use App\Services\Transportation\Contracts\MapProvider;
use Illuminate\Support\Facades\Http;

/**
 * Free OpenStreetMap/Nominatim provider. No API key required.
 * Geocoding usage must stay within Nominatim's usage policy (1 req/s);
 * the admin UI debounces requests accordingly.
 */
class NominatimProvider implements MapProvider
{
    protected const GEOCODE_URL = 'https://nominatim.openstreetmap.org/search';
    protected const REVERSE_URL = 'https://nominatim.openstreetmap.org/reverse';
    // Conservative urban average for school buses (m/s) ~ 22 km/h with stops.
    protected const DEFAULT_SPEED_MPS = 6.1;

    public function key(): string
    {
        return 'osm';
    }

    public function geocodeAddress(string $address): ?array
    {
        if (trim($address) === '') {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => config('app.name', 'school') . '-transportation/1.0'])
                ->get(self::GEOCODE_URL, [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'addressdetails' => 0,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $first = $response->json()[0] ?? null;
            if (! $first || ! isset($first['lat'], $first['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $first['lat'],
                'lng' => (float) $first['lon'],
                'display_name' => $first['display_name'] ?? $address,
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function reverseGeocode(float $lat, float $lng): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => config('app.name', 'school') . '-transportation/1.0'])
                ->get(self::REVERSE_URL, [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'jsonv2',
                ]);

            if (! $response->ok()) {
                return null;
            }

            return $response->json('display_name');
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function estimateEtaMinutes(float $distanceMeters, ?float $speedMps = null): ?float
    {
        $speed = ($speedMps !== null && $speedMps > 0.5) ? $speedMps : self::DEFAULT_SPEED_MPS;

        return round(($distanceMeters / $speed) / 60, 1);
    }
}
