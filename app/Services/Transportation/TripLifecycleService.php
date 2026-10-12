<?php

namespace App\Services\Transportation;

use App\Models\Transportation\Bus;
use App\Models\Transportation\StudentTransportProfile;
use App\Models\Transportation\TransportationSetting;
use App\Models\Transportation\TransportationTrip;
use App\Models\Transportation\TripEvent;
use App\Models\Transportation\TripStudent;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * State machine + side effects for trips.
 *
 * Status transitions:
 *   SCHEDULED/READY -> IN_PROGRESS (driver starts)
 *   IN_PROGRESS     -> COMPLETED  (driver ends)
 *   * (not ended)   -> CANCELLED  (admin/driver cancels)
 *   IN_PROGRESS     -> EMERGENCY  (driver triggers; reversible by ending)
 */
class TripLifecycleService
{
    public function __construct(protected TransportationNotifier $notifier)
    {
    }

    /**
     * Start a trip: snapshot the roster with coordinates, flip statuses,
     * record the event, notify parents. Idempotent by status.
     */
    public function start(TransportationTrip $trip, $actor): TransportationTrip
    {
        if ($trip->status === 'IN_PROGRESS') {
            return $trip; // already started — safe retry
        }

        if (! in_array($trip->status, ['SCHEDULED', 'READY'], true)) {
            throw new ConflictHttpException("Trip cannot be started from status {$trip->status}.");
        }

        return DB::transaction(function () use ($trip, $actor) {
            $settings = TransportationSetting::forSchool($trip->school_id);

            $trip->forceFill([
                'status' => 'IN_PROGRESS',
                'started_at' => now(),
                'settings_snapshot' => [
                    'geofence_radius_m' => $settings->geofence_radius_m,
                    'gps_interval_s' => $settings->gps_interval_s,
                ],
            ])->save();

            $this->snapshotRoster($trip);

            $trip->bus?->forceFill(['status' => 'ON_TRIP'])->save();

            TripEvent::create([
                'school_id' => $trip->school_id,
                'trip_id' => $trip->id,
                'type' => 'TRIP_STARTED',
                'actor_id' => $actor?->id,
                'actor_type' => $actor ? get_class($actor) : null,
            ]);

            $trip->load(['tripStudents.student', 'route', 'bus']);

            $this->notifier->notifyTripStarted($trip);

            return $trip;
        });
    }

    /** Build the immutable trip roster from the route's ordered stops. */
    protected function snapshotRoster(TransportationTrip $trip): void
    {
        $route = $trip->route()->with('stops.student.transportProfile')->first();

        $rows = [];
        foreach ($route?->stops ?? [] as $stop) {
            $profile = $stop->student?->transportProfile;
            if (! $profile || ! $profile->is_enabled) {
                continue;
            }

            $pickup = $profile->pickupFor($trip->direction);
            $dropoff = $profile->dropoffFor($trip->direction);

            $rows[] = [
                'trip_id' => $trip->id,
                'student_id' => $stop->student_id,
                'stop_order' => $stop->stop_order,
                'pickup_address' => $pickup['address'],
                'pickup_lat' => $pickup['lat'],
                'pickup_lng' => $pickup['lng'],
                'dropoff_address' => $dropoff['address'],
                'dropoff_lat' => $dropoff['lat'],
                'dropoff_lng' => $dropoff['lng'],
                'status' => 'WAITING',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            TripStudent::insert($chunk);
        }
    }

    public function end(TransportationTrip $trip, $actor): TransportationTrip
    {
        if ($trip->status === 'COMPLETED') {
            return $trip;
        }

        if (! $trip->isActive()) {
            throw new ConflictHttpException("Trip cannot be ended from status {$trip->status}.");
        }

        return DB::transaction(function () use ($trip, $actor) {
            $trip->forceFill([
                'status' => 'COMPLETED',
                'ended_at' => now(),
            ])->save();

            $trip->bus?->forceFill(['status' => 'AVAILABLE'])->save();

            TripEvent::create([
                'school_id' => $trip->school_id,
                'trip_id' => $trip->id,
                'type' => 'TRIP_ENDED',
                'actor_id' => $actor?->id,
                'actor_type' => $actor ? get_class($actor) : null,
            ]);

            $trip->load('tripStudents.student.parent');
            $this->notifier->notifyTripEnded($trip);

            return $trip;
        });
    }

    public function cancel(TransportationTrip $trip, $actor, ?string $reason = null): TransportationTrip
    {
        if (in_array($trip->status, ['COMPLETED', 'CANCELLED'], true)) {
            throw new ConflictHttpException("Trip already {$trip->status}.");
        }

        return DB::transaction(function () use ($trip, $actor, $reason) {
            $wasActive = $trip->isActive();

            $trip->forceFill([
                'status' => 'CANCELLED',
                'cancelled_reason' => $reason,
                'ended_at' => $wasActive ? now() : null,
            ])->save();

            if ($wasActive) {
                $trip->bus?->forceFill(['status' => 'AVAILABLE'])->save();
            }

            TripEvent::create([
                'school_id' => $trip->school_id,
                'trip_id' => $trip->id,
                'type' => 'TRIP_CANCELLED',
                'meta' => $reason ? ['reason' => $reason] : null,
                'actor_id' => $actor?->id,
                'actor_type' => $actor ? get_class($actor) : null,
            ]);

            $trip->load('tripStudents.student.parent');
            $this->notifier->notifyTripCancelled($trip, $reason);

            return $trip;
        });
    }

    /**
     * Mark a student picked up / dropped off. Idempotent:
     *  - client_event_id unique key dedupes retries from the driver app,
     *  - status transitions are guarded (WAITING -> PICKED_UP -> DROPPED_OFF).
     *
     * @return array{0: TripStudent, 1: bool} the record and whether it changed
     */
    public function markStudent(TransportationTrip $trip, TripStudent $tripStudent, string $action, ?array $gps, ?string $clientEventId, $actor): array
    {
        if (! $trip->isActive()) {
            throw new ConflictHttpException('Trip is not active.');
        }

        // Idempotent retry of the same event.
        if ($clientEventId && $tripStudent->client_event_id === $clientEventId) {
            return [$tripStudent, false];
        }

        $expected = $action === 'picked_up' ? 'WAITING' : 'PICKED_UP';
        if ($tripStudent->status !== $expected) {
            throw new ConflictHttpException("Student status is {$tripStudent->status}; expected {$expected}.");
        }

        return DB::transaction(function () use ($trip, $tripStudent, $action, $gps, $clientEventId, $actor) {
            $attributes = [
                'status' => $action === 'picked_up' ? 'PICKED_UP' : 'DROPPED_OFF',
                'client_event_id' => $clientEventId,
            ];

            if ($action === 'picked_up') {
                $attributes['picked_up_at'] = now();
                $attributes['picked_up_lat'] = $gps['lat'] ?? null;
                $attributes['picked_up_lng'] = $gps['lng'] ?? null;
            } else {
                $attributes['dropped_off_at'] = now();
                $attributes['dropped_off_lat'] = $gps['lat'] ?? null;
                $attributes['dropped_off_lng'] = $gps['lng'] ?? null;
            }

            $tripStudent->forceFill($attributes)->save();

            TripEvent::create([
                'school_id' => $trip->school_id,
                'trip_id' => $trip->id,
                'student_id' => $tripStudent->student_id,
                'type' => $action === 'picked_up' ? 'STUDENT_PICKED_UP' : 'STUDENT_DROPPED_OFF',
                'latitude' => $gps['lat'] ?? null,
                'longitude' => $gps['lng'] ?? null,
                'actor_id' => $actor?->id,
                'actor_type' => $actor ? get_class($actor) : null,
            ]);

            $tripStudent->load('student.parent');

            if ($action === 'picked_up') {
                $this->notifier->notifyPickedUp($trip, $tripStudent);
            } else {
                $this->notifier->notifyDroppedOff($trip, $tripStudent);
            }

            return [$tripStudent, true];
        });
    }

    public function emergency(TransportationTrip $trip, ?array $gps, $actor): TransportationTrip
    {
        if (! $trip->isActive()) {
            throw new ConflictHttpException('Trip is not active.');
        }

        return DB::transaction(function () use ($trip, $gps, $actor) {
            $trip->forceFill([
                'status' => 'EMERGENCY',
                'emergency_at' => now(),
            ])->save();

            TripEvent::create([
                'school_id' => $trip->school_id,
                'trip_id' => $trip->id,
                'type' => 'EMERGENCY',
                'latitude' => $gps['lat'] ?? null,
                'longitude' => $gps['lng'] ?? null,
                'actor_id' => $actor?->id,
                'actor_type' => $actor ? get_class($actor) : null,
            ]);

            $trip->load(['route', 'tripStudents.student.parent']);
            $this->notifier->notifyEmergency($trip, $actor);

            return $trip;
        });
    }
}
