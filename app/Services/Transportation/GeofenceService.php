<?php

namespace App\Services\Transportation;

use App\Models\Transportation\TransportationSetting;
use App\Models\Transportation\TransportationTrip;
use App\Models\Transportation\TripEvent;


/**
 * Server-side geofencing: on every location update of an active trip,
 * measure the distance to the next pending stop of each student on the
 * trip roster. When the bus enters the school's geofence radius, fire a
 * fire-once "approaching" event + parent notification.
 *
 * Only students with coordinates are evaluated; drivers never need to
 * trigger approaching alerts manually.
 */
class GeofenceService
{
    protected \App\Services\Transportation\Contracts\MapProvider $map;

    public function __construct()
    {
        // Resolved per evaluation; the provider is configurable per school.
        $this->map = MapService::provider();
    }

    /**
     * Evaluate all pending stops for a trip against the latest bus position.
     *
     * @return TripEvent[] events created during this evaluation
     */
    public function evaluate(TransportationTrip $trip, float $lat, float $lng): array
    {
        if (! $trip->isActive()) {
            return [];
        }

        $settings = TransportationSetting::forSchool($trip->school_id);
        if (! $settings->approaching_notifications) {
            return [];
        }

        $radius = max(50, (int) $settings->geofence_radius_m);
        $events = [];

        foreach ($trip->tripStudents as $tripStudent) {
            $stop = $tripStudent->pendingStop();
            if (! $stop) {
                continue;
            }

            $sentFlag = $stop['kind'] === 'pickup'
                ? 'approaching_pickup_sent'
                : 'approaching_dropoff_sent';

            if ($tripStudent->getAttribute($sentFlag)) {
                continue;
            }

            $distance = $this->map->distanceMeters($lat, $lng, $stop['lat'], $stop['lng']);
            if ($distance > $radius) {
                continue;
            }

            $type = $stop['kind'] === 'pickup'
                ? TripEvent::TYPES[1] // BUS_APPROACHING_PICKUP
                : TripEvent::TYPES[3]; // BUS_APPROACHING_DROPOFF

            $events[] = $this->fireApproaching($trip, $tripStudent, $type, $lat, $lng, $distance);
        }

        return $events;
    }

    protected function fireApproaching(TransportationTrip $trip, $tripStudent, string $type, float $lat, float $lng, float $distance)
    {
        $isPickup = $type === TripEvent::TYPES[1];

        $tripStudent->forceFill([
            $isPickup ? 'approaching_pickup_sent' : 'approaching_dropoff_sent' => true,
        ])->save();

        $event = TripEvent::create([
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'student_id' => $tripStudent->student_id,
            'type' => $type,
            'latitude' => $lat,
            'longitude' => $lng,
            'meta' => ['distance_m' => round($distance, 1)],
        ]);

        app(TransportationNotifier::class)->notifyApproaching($trip, $tripStudent, $isPickup);

        return $event;
    }
}
