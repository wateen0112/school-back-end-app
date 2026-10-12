<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Transportation\RouteStop;
use App\Models\Transportation\TransportationRoute;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RouteController extends TransportationController
{
    public function index(Request $request)
    {
        try {
            $query = $this->scopeToSchool(
                TransportationRoute::with(['bus', 'driver', 'stops.student'])->orderBy('name'),
                $request
            );

            if ($request->filled('direction')) {
                $query->where('direction', $request->direction);
            }
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            }

            return $this->ok($query->paginate($request->integer('per_page', 15)), 'Routes retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving routes', 500, $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $route = TransportationRoute::with(['branch', 'bus', 'driver', 'stops.student.transportProfile'])->findOrFail($id);
        $this->assertSchool($route->school_id, $request);

        return $this->ok($route, 'Route retrieved successfully');
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        $schoolId = $this->schoolId($request);
        if (! $schoolId) {
            return $this->fail('A school context is required', 400);
        }

        try {
            $route = DB::transaction(function () use ($request, $schoolId) {
                $route = TransportationRoute::create([
                    'school_id' => $schoolId,
                    'branch_id' => $request->branch_id,
                    'name' => $request->name,
                    'code' => $request->code,
                    'direction' => $request->direction ?? 'HOME_TO_SCHOOL',
                    'bus_id' => $request->bus_id,
                    'driver_user_id' => $request->driver_user_id,
                    'scheduled_start_time' => $request->scheduled_start_time,
                    'active_days' => $request->active_days,
                    'is_active' => $request->boolean('is_active', true),
                ]);

                $this->replaceStops($route, $request->input('stop_student_ids', []));

                return $route;
            });

            AuditLogger::log('transportation.route.created', TransportationRoute::class, $route->id, "Route {$route->name} created");

            return $this->ok($route->load('stops.student'), 'Route created successfully', 201);
        } catch (\Exception $e) {
            return $this->fail('Error creating route', 500, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $route = TransportationRoute::findOrFail($id);
        $this->assertSchool($route->school_id, $request);

        $validator = $this->validator($request, true);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            DB::transaction(function () use ($request, $route) {
                $route->update(collect($validator->validated())->except('stop_student_ids')->toArray());

                if ($request->has('stop_student_ids')) {
                    $this->replaceStops($route, $request->input('stop_student_ids', []));
                }
            });

            AuditLogger::log('transportation.route.updated', TransportationRoute::class, $route->id, "Route {$route->name} updated");

            return $this->ok($route->fresh()->load('stops.student'), 'Route updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating route', 500, $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $route = TransportationRoute::findOrFail($id);
        $this->assertSchool($route->school_id, $request);

        try {
            if ($route->trips()->exists()) {
                $route->update(['is_active' => false]);
                AuditLogger::log('transportation.route.archived', TransportationRoute::class, $route->id, "Route {$route->name} archived (has trips)");
                return $this->ok(null, 'Route archived (kept for trip history)');
            }

            $route->delete();
            AuditLogger::log('transportation.route.deleted', TransportationRoute::class, $route->id, "Route {$route->name} deleted");

            return $this->ok(null, 'Route deleted successfully');
        } catch (\Exception $e) {
            return $this->fail('Error deleting route', 500, $e->getMessage());
        }
    }

    /**
     * Replace the ordered stop list in one shot.
     * Body: { "stop_student_ids": [12, 7, 3] } — order defines stop_order.
     */
    public function syncStops(Request $request, $id)
    {
        $route = TransportationRoute::findOrFail($id);
        $this->assertSchool($route->school_id, $request);

        $validator = Validator::make($request->all(), [
            'stop_student_ids' => 'required|array',
            'stop_student_ids.*' => 'distinct|exists:students,id',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            DB::transaction(fn () => $this->replaceStops($route, $request->stop_student_ids));

            AuditLogger::log('transportation.route.stops_updated', TransportationRoute::class, $route->id, "Route {$route->name} stops reordered");

            return $this->ok($route->fresh()->load('stops.student'), 'Stops updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating stops', 500, $e->getMessage());
        }
    }

    /** Rebuild the ordered stop list (shared by store/update/sync). */
    protected function replaceStops(TransportationRoute $route, array $studentIds): void
    {
        RouteStop::where('route_id', $route->id)->delete();

        $rows = [];
        foreach (array_values($studentIds) as $index => $studentId) {
            $rows[] = [
                'route_id' => $route->id,
                'student_id' => $studentId,
                'stop_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            RouteStop::insert($chunk);
        }
    }

    protected function validator(Request $request, bool $partial = false)
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return Validator::make($request->all(), [
            'name' => "{$required}|string|max:150",
            'code' => 'nullable|string|max:50',
            'direction' => 'nullable|in:HOME_TO_SCHOOL,SCHOOL_TO_HOME',
            'branch_id' => 'nullable|exists:school_branches,id',
            'bus_id' => 'nullable|exists:transportation_buses,id',
            'driver_user_id' => 'nullable|exists:users,id',
            'scheduled_start_time' => 'nullable|date_format:H:i',
            'active_days' => 'nullable|array',
            'active_days.*' => 'integer|min:1|max:7',
            'is_active' => 'nullable|boolean',
            'stop_student_ids' => 'nullable|array',
            'stop_student_ids.*' => 'distinct|exists:students,id',
        ]);
    }
}
