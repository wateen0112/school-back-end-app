<?php

namespace App\Http\Controllers\Api\Transportation\Parent;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\My_Parent;
use App\Models\Transportation\TransportationSetting;
use App\Models\Transportation\TransportationTrip;
use App\Models\Transportation\TripStudent;
use App\Services\Transportation\MapService;
use Illuminate\Http\Request;

/**
 * Parent mobile endpoints. Privacy rule enforced on EVERY call:
 *   My_Parent -> owns student -> student is on the trip roster
 * before any transportation data (especially live locations) is returned.
 * Parents never receive trip history.
 */
class ParentTransportController extends TransportationController
{
    /** The signed-in parent's children and their transportation state. */
    public function children(Request $request)
    {
        $parent = $this->parent($request);

        try {
            $children = $parent->students()
                ->with(['transportProfile.morningRoute.bus', 'transportProfile.afternoonRoute.bus'])
                ->get()
                ->map(function ($student) {
                    $profile = $student->transportProfile;
                    $active = $this->activeTripForStudent($student->id);

                    return [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'transportation_enabled' => (bool) ($profile?->is_enabled),
                        'has_morning_route' => (bool) $profile?->morning_route_id,
                        'has_afternoon_route' => (bool) $profile?->afternoon_route_id,
                        'active_trip' => $active ? $this->shapeActiveTrip($active, $student->id) : null,
                    ];
                });

            return $this->ok($children, 'Children transportation retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving children transportation', 500, $e->getMessage());
        }
    }

    /** Full live view of one child's active trip. */
    public function activeTrip(Request $request, $studentId)
    {
        $this->parent($request);

        $student = $this->ownStudent($request, $studentId);

        $trip = $this->activeTripForStudent($student->id);

        if (! $trip) {
            return $this->ok([
                'student_id' => $student->id,
                'status' => 'NOT_STARTED',
                'trip' => null,
            ], 'No active trip for this child');
        }

        return $this->ok($this->shapeActiveTrip($trip, $student->id, true), 'Active trip retrieved successfully');
    }

    /** Live bus position for a trip the parent's child is part of. */
    public function busLocation(Request $request, $tripId)
    {
        $parent = $this->parent($request);

        $trip = TransportationTrip::with('latestLocation')->findOrFail($tripId);

        // Verify: this parent's child is on this trip's roster.
        $onRoster = TripStudent::where('trip_id', $trip->id)
            ->whereIn('student_id', $parent->students()->pluck('id'))
            ->exists();
        abort_unless($onRoster, 404, 'Not found.');

        // School may disable parent live tracking entirely.
        $settings = TransportationSetting::forSchool($trip->school_id);
        abort_unless($settings->parent_live_tracking, 403, 'Live tracking is disabled by the school.');

        abort_unless($trip->isActive(), 409, 'Trip is not active.');

        $location = $trip->latestLocation;

        $map = MapService::forSchool($trip->school_id);
        $distance = null;
        $eta = null;
        if ($location) {
            // Distance/ETA to the child's pending stop only — never to other students.
            $mine = TripStudent::where('trip_id', $trip->id)
                ->whereIn('student_id', $parent->students()->pluck('id'))
                ->orderBy('stop_order')
                ->get();
            $target = $mine->firstWhere('status', 'WAITING') ?? $mine->firstWhere('status', 'PICKED_UP');
            $stop = $target?->pendingStop();
            if ($stop) {
                $distance = $map->distanceMeters($location->latitude, $location->longitude, $stop['lat'], $stop['lng']);
                $eta = $map->estimateEtaMinutes($distance, $location->speed);
            }
        }

        return $this->ok([
            'trip_id' => $trip->id,
            'status' => $trip->status,
            'location' => $location ? [
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'speed' => $location->speed,
                'heading' => $location->heading,
                'recorded_at' => $location->recorded_at,
            ] : null,
            'distance_to_my_stop_m' => $distance !== null ? round($distance) : null,
            'eta_minutes' => $eta,
        ], 'Bus location retrieved successfully');
    }

    // ------------------------------------------------------------ helpers

    protected function parent(Request $request): My_Parent
    {
        // Resolved through the parent guard explicitly: the auth.any
        // middleware sets that guard for parent sessions/tokens.
        $user = \Illuminate\Support\Facades\Auth::guard('parent')->user() ?? $request->user();
        abort_unless($user instanceof My_Parent, 403, 'Parents only.');
        return $user;
    }

    /** A student owned by the signed-in parent, or 404 (never 403 — no existence leak). */
    protected function ownStudent(Request $request, $studentId)
    {
        $student = $this->parent($request)
            ->students()
            ->with('transportProfile')
            ->find($studentId);

        abort_unless($student, 404, 'Not found.');

        return $student;
    }

    protected function activeTripForStudent(int $studentId): ?TransportationTrip
    {
        $tripStudent = TripStudent::where('student_id', $studentId)
            ->whereHas('trip', fn ($q) => $q->whereIn('status', ['SCHEDULED', 'READY', 'IN_PROGRESS', 'EMERGENCY']))
            ->whereDate('created_at', '>=', today()->subDays(1))
            ->latest('id')
            ->first();

        if (! $tripStudent) {
            return null;
        }

        return TransportationTrip::with(['route', 'bus', 'driver', 'latestLocation'])
            ->find($tripStudent->trip_id);
    }

    protected function shapeActiveTrip(TransportationTrip $trip, int $studentId, bool $detail = false): array
    {
        $mine = TripStudent::where('trip_id', $trip->id)
            ->where('student_id', $studentId)
            ->first();

        $shape = [
            'trip_id' => $trip->id,
            'status' => $this->deriveStatus($trip, $mine),
            'raw_status' => $trip->status,
            'direction' => $trip->direction,
            'scheduled_start_time' => $trip->scheduled_start_time,
            'started_at' => $trip->started_at,
            'route' => $trip->route ? ['id' => $trip->route->id, 'name' => $trip->route->name] : null,
            'bus' => $trip->bus ? ['id' => $trip->bus->id, 'bus_number' => $trip->bus->bus_number, 'name' => $trip->bus->name] : null,
            'driver_name' => $trip->driver?->name,
            'my_child' => $mine ? [
                'status' => $mine->status,
                'pickup' => ['lat' => $mine->pickup_lat, 'lng' => $mine->pickup_lng, 'address' => $mine->pickup_address],
                'dropoff' => ['lat' => $mine->dropoff_lat, 'lng' => $mine->dropoff_lng, 'address' => $mine->dropoff_address],
                'picked_up_at' => $mine->picked_up_at,
                'dropped_off_at' => $mine->dropped_off_at,
            ] : null,
            'bus_location' => $trip->latestLocation ? [
                'lat' => $trip->latestLocation->latitude,
                'lng' => $trip->latestLocation->longitude,
                'recorded_at' => $trip->latestLocation->recorded_at,
            ] : null,
        ];

        return $shape;
    }

    /** Parent-facing, human-friendly status derived from trip + child state. */
    protected function deriveStatus(TransportationTrip $trip, ?TripStudent $mine): string
    {
        if ($trip->status === 'EMERGENCY') {
            return 'EMERGENCY';
        }
        if (in_array($trip->status, ['SCHEDULED', 'READY'], true)) {
            return 'NOT_STARTED';
        }
        if ($trip->status === 'CANCELLED') {
            return 'CANCELLED';
        }
        if ($trip->status === 'COMPLETED') {
            return 'COMPLETED';
        }
        // IN_PROGRESS
        if (! $mine) {
            return 'IN_TRANSIT';
        }
        if ($mine->status === 'DROPPED_OFF') {
            return 'DROPPED_OFF';
        }
        if ($mine->status === 'PICKED_UP') {
            return $mine->approaching_dropoff_sent ? 'APPROACHING_DROPOFF' : 'PICKED_UP';
        }
        return $mine->approaching_pickup_sent ? 'APPROACHING' : 'BUS_ON_THE_WAY';
    }
}
