<?php

namespace App\Services\Transportation;

use App\Models\Transportation\LocationUpdate;
use App\Models\Transportation\TransportationTrip;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Ingests driver GPS updates for active trips and runs the geofence
 * evaluation against the fresh position.
 */
class LocationService
{
    public function __construct(protected GeofenceService $geofence)
    {
    }

    /**
     * @param array{latitude:float, longitude:float, accuracy?:float, speed?:float, heading?:float, recorded_at?:string} $data
     */
    public function record(TransportationTrip $trip, $driver, array $data): LocationUpdate
    {
        if (! $trip->isActive()) {
            throw new ConflictHttpException('GPS updates are only accepted while the trip is active.');
        }

        if ($trip->driver_user_id !== $driver->id) {
            abort(403, 'You are not the assigned driver for this trip.');
        }

        $update = LocationUpdate::create([
            'trip_id' => $trip->id,
            'bus_id' => $trip->bus_id,
            'driver_user_id' => $driver->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? null,
            'heading' => $data['heading'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        $this->geofence->evaluate($trip, (float) $data['latitude'], (float) $data['longitude']);

        return $update;
    }

    /** Latest known position of a trip, null when nothing received yet. */
    public function latestFor(TransportationTrip $trip): ?LocationUpdate
    {
        return $trip->latestLocation;
    }

    /**
     * Prune old location rows. Keeps the trail of recent trips bounded —
     * full GPS history is intentionally not retained (privacy + storage).
     */
    public function prune(int $keepPerTrip = 2000, int $keepDays = 30): int
    {
        $cutoff = now()->subDays($keepDays);

        $deleted = LocationUpdate::where('recorded_at', '<', $cutoff)->delete();

        $heavyTripIds = LocationUpdate::selectRaw('trip_id, COUNT(*) as c')
            ->groupBy('trip_id')
            ->having('c', '>', $keepPerTrip)
            ->pluck('trip_id');

        foreach ($heavyTripIds as $tripId) {
            $idsToKeep = LocationUpdate::where('trip_id', $tripId)
                ->orderByDesc('recorded_at')
                ->limit($keepPerTrip)
                ->pluck('id');

            $deleted += LocationUpdate::where('trip_id', $tripId)
                ->whereNotIn('id', $idsToKeep)
                ->delete();
        }

        return $deleted;
    }
}
