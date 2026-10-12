<?php

namespace App\Http\Controllers\Api\Transportation\Driver;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Transportation\TransportationTrip;
use App\Models\Transportation\TripStudent;
use App\Services\Transportation\LocationService;
use App\Services\Transportation\TripLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Driver mobile endpoints. Authorization model:
 *   Driver (App\User + active driver profile) -> only trips where
 *   driver_user_id = their id -> only that trip's roster.
 * No permission middleware: drivers are not school-staff RBAC users.
 */
class DriverTripController extends TransportationController
{
    /** Driver home: assigned bus, routes and today's trips. */
    public function home(Request $request)
    {
        // Resolved outside try/catch: authorization failures must surface
        // as 403, never be swallowed into a 500.
        $profile = $this->driverProfile($request);
        $user = $this->driverUser($request);

        try {

            $todayTrips = TransportationTrip::where('driver_user_id', $user->id)
                ->whereDate('trip_date', today())
                ->whereIn('status', ['SCHEDULED', 'READY', 'IN_PROGRESS', 'EMERGENCY'])
                ->with(['route:id,name,direction', 'bus:id,bus_number,name'])
                ->orderBy('scheduled_start_time')
                ->get();

            $activeTrip = $todayTrips->firstWhere('status', 'IN_PROGRESS')
                ?? $todayTrips->firstWhere('status', 'EMERGENCY');

            return $this->ok([
                'driver' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_no' => $profile->employee_no,
                    'phone' => $profile->phone,
                ],
                'assigned_bus' => $profile->assignedBus,
                'today_trips' => $todayTrips,
                'active_trip' => $activeTrip?->id,
            ], 'Driver home retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving driver home', 500, $e->getMessage());
        }
    }

    public function today(Request $request)
    {
        $user = $this->driverUser($request);

        try {
            $trips = TransportationTrip::where('driver_user_id', $user->id)
                ->whereDate('trip_date', today())
                ->with(['route:id,name,direction', 'bus:id,bus_number,name'])
                ->orderBy('scheduled_start_time')
                ->get()
                ->map(fn ($t) => $this->shapeTripSummary($t));

            return $this->ok($trips, "Today's trips retrieved successfully");
        } catch (\Exception $e) {
            return $this->fail("Error retrieving today's trips", 500, $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $trip = $this->findDriverTrip($request, $id);

        return $this->ok($this->shapeTripDetail($trip), 'Trip retrieved successfully');
    }

    public function start(Request $request, $id)
    {
        $trip = $this->findDriverTrip($request, $id);

        // A driver can only run one active trip at a time.
        $existing = TransportationTrip::where('driver_user_id', $this->driverUser($request)->id)
            ->whereIn('status', ['IN_PROGRESS', 'EMERGENCY'])
            ->where('id', '!=', $trip->id)
            ->exists();
        if ($existing) {
            return $this->fail('You already have an active trip. End it before starting another.', 409);
        }

        try {
            $trip = app(TripLifecycleService::class)->start($trip, $this->driverUser($request));
            return $this->ok($this->shapeTripDetail($trip->fresh(['tripStudents.student'])), 'Trip started successfully');
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->fail('Error starting trip', 500, $e->getMessage());
        }
    }

    public function end(Request $request, $id)
    {
        $trip = $this->findDriverTrip($request, $id);

        try {
            $trip = app(TripLifecycleService::class)->end($trip, $this->driverUser($request));
            return $this->ok($this->shapeTripSummary($trip->fresh()), 'Trip completed successfully');
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->fail('Error ending trip', 500, $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $trip = $this->findDriverTrip($request, $id);

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $trip = app(TripLifecycleService::class)->cancel($trip, $this->driverUser($request), $validator->validated()['reason'] ?? null);
            return $this->ok($this->shapeTripSummary($trip->fresh()), 'Trip cancelled successfully');
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->fail('Error cancelling trip', 500, $e->getMessage());
        }
    }

    /** GPS update from the driver app. Single point per request. */
    public function location(Request $request, $id)
    {
        $trip = $this->findDriverTrip($request, $id);

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            app(LocationService::class)->record($trip, $this->driverUser($request), $validator->validated());
            return $this->ok(['received' => true], 'Location updated');
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->fail('Error recording location', 500, $e->getMessage());
        }
    }

    /** The current "next stop" card for the driver UI. */
    public function nextStop(Request $request, $id)
    {
        $trip = $this->findDriverTrip($request, $id);
        $trip->loadMissing('latestLocation', 'tripStudents.student');

        $next = $trip->tripStudents->firstWhere('status', 'WAITING')
            ?? $trip->tripStudents->firstWhere('status', 'PICKED_UP');

        if (! $next) {
            return $this->ok(['next' => null, 'message' => 'All stops completed'], 'No pending stops');
        }

        $stop = $next->pendingStop();
        $map = \App\Services\Transportation\MapService::forSchool($trip->school_id);
        $distance = null;
        $eta = null;
        if ($stop && $trip->latestLocation) {
            $distance = $map->distanceMeters(
                $trip->latestLocation->latitude,
                $trip->latestLocation->longitude,
                $stop['lat'], $stop['lng']
            );
            $eta = $map->estimateEtaMinutes($distance, $trip->latestLocation->speed);
        }

        return $this->ok([
            'next' => [
                'trip_student_id' => $next->id,
                'student' => [
                    'id' => $next->student_id,
                    'name' => $next->student?->name,
                    'photo' => $next->student?->images?->first()?->filename ?? null,
                ],
                'stop_order' => $next->stop_order,
                'status' => $next->status,
                'stop_kind' => $stop['kind'] ?? null,
                'address' => $stop['kind'] === 'pickup' ? $next->pickup_address : $next->dropoff_address,
                'lat' => $stop['lat'] ?? null,
                'lng' => $stop['lng'] ?? null,
                'distance_m' => $distance !== null ? round($distance) : null,
                'eta_minutes' => $eta,
            ],
            'remaining' => $trip->tripStudents->where('status', 'WAITING')->count(),
        ], 'Next stop retrieved successfully');
    }

    public function pickedUp(Request $request, $id, $tripStudentId)
    {
        return $this->mark($request, $id, $tripStudentId, 'picked_up');
    }

    public function droppedOff(Request $request, $id, $tripStudentId)
    {
        return $this->mark($request, $id, $tripStudentId, 'dropped_off');
    }

    protected function mark(Request $request, $id, $tripStudentId, string $action)
    {
        $trip = $this->findDriverTrip($request, $id);

        $tripStudent = TripStudent::where('trip_id', $trip->id)
            ->where('id', $tripStudentId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'client_event_id' => 'nullable|string|max:64',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        $gps = $request->only(['latitude', 'longitude']);

        try {
            [$tripStudent, $changed] = app(TripLifecycleService::class)
                ->markStudent($trip, $tripStudent, $action, $gps, $request->client_event_id, $this->driverUser($request));

            return $this->ok(
                ['trip_student' => $tripStudent, 'changed' => $changed],
                $action === 'picked_up' ? 'Student marked as picked up' : 'Student marked as dropped off'
            );
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->fail("Error marking student {$action}", 500, $e->getMessage());
        }
    }

    public function emergency(Request $request, $id)
    {
        $trip = $this->findDriverTrip($request, $id);

        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'confirmed' => 'required|accepted', // UI must pass explicit confirmation
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            $trip = app(TripLifecycleService::class)
                ->emergency($trip, $request->only(['latitude', 'longitude']), $this->driverUser($request));

            return $this->ok($this->shapeTripSummary($trip->fresh()), 'Emergency alert sent. Help is on the way.');
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->fail('Error sending emergency alert', 500, $e->getMessage());
        }
    }

    // ------------------------------------------------------------ helpers

    protected function findDriverTrip(Request $request, $id): TransportationTrip
    {
        $this->driverProfile($request);

        $trip = TransportationTrip::findOrFail($id);

        if ((int) $trip->driver_user_id !== (int) $this->driverUser($request)->id) {
            abort(403, 'This trip is not assigned to you.');
        }

        return $trip;
    }

    protected function shapeTripSummary(TransportationTrip $trip): array
    {
        return [
            'id' => $trip->id,
            'status' => $trip->status,
            'direction' => $trip->direction,
            'trip_date' => $trip->trip_date,
            'scheduled_start_time' => $trip->scheduled_start_time,
            'started_at' => $trip->started_at,
            'route' => $trip->route ? ['id' => $trip->route->id, 'name' => $trip->route->name] : null,
            'bus' => $trip->bus ? ['id' => $trip->bus->id, 'bus_number' => $trip->bus->bus_number, 'name' => $trip->bus->name] : null,
            'gps_interval_s' => $trip->settings_snapshot['gps_interval_s'] ?? 10,
        ];
    }

    protected function shapeTripDetail(TransportationTrip $trip): array
    {
        $trip->loadMissing('tripStudents.student', 'route', 'bus', 'latestLocation');

        return $this->shapeTripSummary($trip) + [
            'students' => $trip->tripStudents->map(fn ($ts) => [
                'trip_student_id' => $ts->id,
                'student_id' => $ts->student_id,
                'name' => $ts->student?->name,
                'stop_order' => $ts->stop_order,
                'status' => $ts->status,
                'pickup' => ['lat' => $ts->pickup_lat, 'lng' => $ts->pickup_lng, 'address' => $ts->pickup_address],
                'dropoff' => ['lat' => $ts->dropoff_lat, 'lng' => $ts->dropoff_lng, 'address' => $ts->dropoff_address],
                'picked_up_at' => $ts->picked_up_at,
                'dropped_off_at' => $ts->dropped_off_at,
            ])->values(),
            'location' => $trip->latestLocation ? [
                'lat' => $trip->latestLocation->latitude,
                'lng' => $trip->latestLocation->longitude,
                'recorded_at' => $trip->latestLocation->recorded_at,
            ] : null,
        ];
    }
}
