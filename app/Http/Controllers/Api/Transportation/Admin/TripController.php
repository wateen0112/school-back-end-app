<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Transportation\TransportationRoute;
use App\Models\Transportation\TransportationTrip;
use App\Services\AuditLogger;
use App\Services\Transportation\TripLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TripController extends TransportationController
{
    public function index(Request $request)
    {
        try {
            $query = $this->scopeToSchool(
                TransportationTrip::with(['route', 'bus', 'driver'])
                    ->orderBy('trip_date')
                    ->orderBy('scheduled_start_time'),
                $request
            );

            $this->applyFilters($query, $request);

            return $this->ok($query->paginate($request->integer('per_page', 15)), 'Trips retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving trips', 500, $e->getMessage());
        }
    }

    /** Today's scheduled + active trips for the overview screen. */
    public function today(Request $request)
    {
        try {
            $query = $this->scopeToSchool(
                TransportationTrip::with(['route', 'bus', 'driver'])
                    ->whereDate('trip_date', today())
                    ->orderBy('scheduled_start_time'),
                $request
            );

            return $this->ok($query->get(), "Today's trips retrieved successfully");
        } catch (\Exception $e) {
            return $this->fail("Error retrieving today's trips", 500, $e->getMessage());
        }
    }

    /** Admin-only trip history with full filters. */
    public function history(Request $request)
    {
        try {
            $query = $this->scopeToSchool(
                TransportationTrip::with(['route', 'bus', 'driver'])
                    ->whereIn('status', ['COMPLETED', 'CANCELLED'])
                    ->orderByDesc('trip_date')
                    ->orderByDesc('id'),
                $request
            );

            $this->applyFilters($query, $request);

            return $this->ok($query->paginate($request->integer('per_page', 15)), 'Trip history retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving trip history', 500, $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $trip = TransportationTrip::with([
            'route.stops.student',
            'bus', 'driver',
            'tripStudents.student.parent',
            'latestLocation',
        ])->findOrFail($id);
        $this->assertSchool($trip->school_id, $request);

        $trip->load(['events.student' => fn ($q) => $q->orderByDesc('created_at')->limit(100)]);

        return $this->ok($trip, 'Trip retrieved successfully');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'route_id' => 'required|exists:transportation_routes,id',
            'trip_date' => 'required|date|after_or_equal:today',
            'scheduled_start_time' => 'nullable|date_format:H:i',
            'direction' => 'nullable|in:HOME_TO_SCHOOL,SCHOOL_TO_HOME',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        $route = TransportationRoute::findOrFail($request->route_id);
        $this->assertSchool($route->school_id, $request);

        if (! $route->bus_id || ! $route->driver_user_id) {
            return $this->fail('Route must have a bus and a driver assigned before scheduling trips', 422);
        }

        // One active/scheduled trip per bus+date+direction.
        $clash = TransportationTrip::where('bus_id', $route->bus_id)
            ->whereDate('trip_date', $request->trip_date)
            ->where('direction', $request->direction ?? $route->direction)
            ->whereIn('status', ['SCHEDULED', 'READY', 'IN_PROGRESS', 'EMERGENCY'])
            ->exists();
        if ($clash) {
            return $this->fail('This bus already has a trip for that date and direction', 409);
        }

        try {
            $trip = TransportationTrip::create([
                'school_id' => $route->school_id,
                'route_id' => $route->id,
                'bus_id' => $route->bus_id,
                'driver_user_id' => $route->driver_user_id,
                'direction' => $request->direction ?? $route->direction,
                'trip_date' => $request->trip_date,
                'scheduled_start_time' => $request->scheduled_start_time ?? $route->scheduled_start_time,
                'status' => 'SCHEDULED',
                'created_by' => $request->user()?->id,
            ]);

            AuditLogger::log('transportation.trip.scheduled', TransportationTrip::class, $trip->id, "Trip scheduled for route {$route->name} on {$trip->trip_date}");

            return $this->ok($trip->load('route', 'bus', 'driver'), 'Trip scheduled successfully', 201);
        } catch (\Exception $e) {
            return $this->fail('Error scheduling trip', 500, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $trip = TransportationTrip::findOrFail($id);
        $this->assertSchool($trip->school_id, $request);

        $validator = Validator::make($request->all(), [
            'trip_date' => 'sometimes|required|date',
            'scheduled_start_time' => 'nullable|date_format:H:i',
            'status' => 'nullable|in:SCHEDULED,READY,CANCELLED',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        if ($trip->status === 'CANCELLED' && $request->status !== 'CANCELLED') {
            return $this->fail('A cancelled trip cannot be modified', 409);
        }

        try {
            $trip->update(collect($validator->validated())->except('status')->toArray());

            if ($request->filled('status') && $request->status === 'CANCELLED' && $trip->status !== 'CANCELLED') {
                app(TripLifecycleService::class)->cancel($trip, $request->user(), $request->cancelled_reason);
            }

            AuditLogger::log('transportation.trip.updated', TransportationTrip::class, $trip->id, "Trip {$trip->id} updated");

            return $this->ok($trip->fresh()->load('route', 'bus', 'driver'), 'Trip updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating trip', 500, $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $trip = TransportationTrip::findOrFail($id);
        $this->assertSchool($trip->school_id, $request);

        try {
            app(TripLifecycleService::class)->cancel($trip, $request->user(), $request->input('reason'));

            AuditLogger::log('transportation.trip.cancelled', TransportationTrip::class, $trip->id, "Trip {$trip->id} cancelled");

            return $this->ok($trip->fresh(), 'Trip cancelled successfully');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $this->fail($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->fail('Error cancelling trip', 500, $e->getMessage());
        }
    }

    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('date')) {
            $query->whereDate('trip_date', $request->date);
        }
        if ($request->filled('from')) {
            $query->whereDate('trip_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('trip_date', '<=', $request->to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }
        if ($request->filled('driver_user_id')) {
            $query->where('driver_user_id', $request->driver_user_id);
        }
        if ($request->filled('bus_id')) {
            $query->where('bus_id', $request->bus_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }
    }
}
