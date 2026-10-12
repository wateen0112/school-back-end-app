<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Transportation\TransportationTrip;
use App\Services\Transportation\MapService;
use Illuminate\Http\Request;

/**
 * Near-real-time monitoring for administrators. The dashboard polls this
 * endpoint (React Query) — no second real-time architecture is introduced.
 */
class LiveTrackingController extends TransportationController
{
    /** All currently active trips with latest position + stop progress. */
    public function index(Request $request)
    {
        try {
            $trips = $this->scopeToSchool(
                TransportationTrip::with(['route', 'bus', 'driver', 'latestLocation'])
                    ->whereIn('status', ['IN_PROGRESS', 'EMERGENCY'])
                    ->orderByDesc('started_at'),
                $request
            )->get();

            $data = $trips->map(fn ($trip) => $this->shapeTrip($trip));

            return $this->ok($data, 'Active trips retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving active trips', 500, $e->getMessage());
        }
    }

    /** One active trip with recent trail + full roster state. */
    public function show(Request $request, $id)
    {
        $trip = TransportationTrip::with(['route.stops.student', 'bus', 'driver', 'tripStudents.student'])
            ->findOrFail($id);
        $this->assertSchool($trip->school_id, $request);

        if (! $trip->isActive()) {
            return $this->fail('Trip is not active', 409);
        }

        $trail = $trip->locationUpdates()
            ->select('latitude', 'longitude', 'speed', 'recorded_at')
            ->orderByDesc('recorded_at')
            ->limit(300)
            ->get()
            ->reverse()
            ->values();

        return $this->ok(
            $this->shapeTrip($trip, true) + ['trail' => $trail],
            'Live trip retrieved successfully'
        );
    }

    protected function shapeTrip(TransportationTrip $trip, bool $withRoster = false): array
    {
        $completed = $trip->tripStudents->whereIn('status', ['PICKED_UP', 'DROPPED_OFF'])->count();
        $roster = $trip->tripStudents;

        $shape = [
            'id' => $trip->id,
            'status' => $trip->status,
            'direction' => $trip->direction,
            'started_at' => $trip->started_at,
            'emergency_at' => $trip->emergency_at,
            'route' => $trip->route ? [
                'id' => $trip->route->id,
                'name' => $trip->route->name,
                'direction' => $trip->route->direction,
            ] : null,
            'bus' => $trip->bus ? [
                'id' => $trip->bus->id,
                'bus_number' => $trip->bus->bus_number,
                'name' => $trip->bus->name,
            ] : null,
            'driver' => $trip->driver ? [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
            ] : null,
            'location' => $trip->latestLocation ? [
                'lat' => $trip->latestLocation->latitude,
                'lng' => $trip->latestLocation->longitude,
                'speed' => $trip->latestLocation->speed,
                'heading' => $trip->latestLocation->heading,
                'recorded_at' => $trip->latestLocation->recorded_at,
            ] : null,
            'stats' => [
                'students_total' => $roster->count(),
                'stops_completed' => $completed,
                'stops_remaining' => $roster->where('status', 'WAITING')->count(),
                'on_bus' => $roster->where('status', 'PICKED_UP')->count(),
            ],
        ];

        if ($withRoster) {
            $map = MapService::forSchool($trip->school_id);
            $shape['students'] = $roster->map(function ($ts) use ($map) {
                $stop = $ts->pendingStop();
                return [
                    'id' => $ts->id,
                    'student_id' => $ts->student_id,
                    'name' => $ts->student?->name,
                    'stop_order' => $ts->stop_order,
                    'status' => $ts->status,
                    'pickup' => ['lat' => $ts->pickup_lat, 'lng' => $ts->pickup_lng, 'address' => $ts->pickup_address],
                    'dropoff' => ['lat' => $ts->dropoff_lat, 'lng' => $ts->dropoff_lng, 'address' => $ts->dropoff_address],
                    'pending_distance_m' => $stop ? round($map->distanceMeters(
                        $ts->trip->latestLocation->latitude ?? $stop['lat'],
                        $ts->trip->latestLocation->longitude ?? $stop['lng'],
                        $stop['lat'], $stop['lng']
                    )) : null,
                ];
            })->values();
        }

        return $shape;
    }
}
