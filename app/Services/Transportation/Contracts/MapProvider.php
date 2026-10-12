<?php

namespace App\Services\Transportation\Contracts;

/**
 * Map provider abstraction for the transportation module.
 *
 * The module never talks to a concrete provider (Nominatim/Google/Mapbox)
 * directly — swap the provider via the transportation_settings.map_provider
 * config value without touching geofencing, trips or UI code.
 */
interface MapProvider
{
    /** Provider key, e.g. "osm". */
    public function key(): string;

    /**
     * Geocode a free-text address into coordinates.
     *
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    public function geocodeAddress(string $address): ?array;

    /**
     * Reverse geocode coordinates into a readable address.
     */
    public function reverseGeocode(float $lat, float $lng): ?string;

    /** Great-circle distance in meters between two coordinates. */
    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float;

    /**
     * Rough ETA in minutes. Providers without routing must return an
     * approximation based on distance and a sane urban average speed —
     * never a precise promise.
     */
    public function estimateEtaMinutes(float $distanceMeters, ?float $speedMps = null): ?float;
}
