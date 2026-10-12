<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Student;
use App\Models\Transportation\StudentTransportProfile;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Transportation profiles of existing students — no duplicate student
 * records are ever created; this is a 1:1 extension keyed by student_id.
 */
class StudentTransportController extends TransportationController
{
    /** Students and their transportation profile (paginated, filterable). */
    public function index(Request $request)
    {
        try {
            $query = $this->scopeToSchool(
                Student::with(['transportProfile.morningRoute', 'transportProfile.afternoonRoute', 'parent', 'grade'])
                    ->whereHas('transportProfile')
                    ->orderBy('id'),
                $request
            );

            if ($request->filled('route_id')) {
                $routeId = $request->route_id;
                $query->whereHas('transportProfile', fn ($q) => $q
                    ->where('morning_route_id', $routeId)
                    ->orWhere('afternoon_route_id', $routeId));
            }
            if ($request->filled('is_enabled')) {
                $enabled = $request->boolean('is_enabled');
                $query->whereHas('transportProfile', fn ($q) => $q->where('is_enabled', $enabled));
            }
            if ($request->filled('missing_coordinates')) {
                $query->whereHas('transportProfile', fn ($q) => $q
                    ->whereNull('morning_pickup_lat')
                    ->whereNull('afternoon_dropoff_lat'));
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(fn ($q) => $q->where('name->en', 'like', "%{$search}%")
                    ->orWhere('name->ar', 'like', "%{$search}%"));
            }

            return $this->ok($query->paginate($request->integer('per_page', 15)), 'Transportation students retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving transportation students', 500, $e->getMessage());
        }
    }

    /** All students of the school (for the assignment picker), flagged with profile state. */
    public function availableStudents(Request $request)
    {
        try {
            $students = $this->scopeToSchool(
                Student::select('id', 'name', 'school_id', 'parent_id', 'Grade_id')
                    ->with(['transportProfile:id,student_id,is_enabled', 'parent:id,Name_Father'])
                    ->orderBy('id'),
                $request
            )->get();

            return $this->ok($students, 'Students retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving students', 500, $e->getMessage());
        }
    }

    public function show(Request $request, $studentId)
    {
        $student = Student::with(['transportProfile.morningRoute', 'transportProfile.afternoonRoute', 'parent'])
            ->findOrFail($studentId);
        $this->assertSchool($student->school_id, $request);

        return $this->ok($student, 'Student transportation profile retrieved successfully');
    }

    /** Create or update the transportation profile (assignments + coordinates). */
    public function upsert(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);
        $this->assertSchool($student->school_id, $request);

        $validator = Validator::make($request->all(), [
            'is_enabled' => 'nullable|boolean',
            'morning_route_id' => 'nullable|exists:transportation_routes,id',
            'afternoon_route_id' => 'nullable|exists:transportation_routes,id',
            'morning_pickup_address' => 'nullable|string|max:255',
            'morning_pickup_lat' => 'nullable|numeric|between:-90,90',
            'morning_pickup_lng' => 'nullable|numeric|between:-180,180',
            'morning_pickup_time' => 'nullable|date_format:H:i',
            'morning_dropoff_address' => 'nullable|string|max:255',
            'morning_dropoff_lat' => 'nullable|numeric|between:-90,90',
            'morning_dropoff_lng' => 'nullable|numeric|between:-180,180',
            'morning_dropoff_time' => 'nullable|date_format:H:i',
            'afternoon_pickup_address' => 'nullable|string|max:255',
            'afternoon_pickup_lat' => 'nullable|numeric|between:-90,90',
            'afternoon_pickup_lng' => 'nullable|numeric|between:-180,180',
            'afternoon_pickup_time' => 'nullable|date_format:H:i',
            'afternoon_dropoff_address' => 'nullable|string|max:255',
            'afternoon_dropoff_lat' => 'nullable|numeric|between:-90,90',
            'afternoon_dropoff_lng' => 'nullable|numeric|between:-180,180',
            'afternoon_dropoff_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        // Route assignments must reference routes of the same school.
        foreach (['morning_route_id', 'afternoon_route_id'] as $field) {
            if ($request->filled($field)) {
                $route = \App\Models\Transportation\TransportationRoute::find($request->$field);
                $this->assertSchool($route?->school_id, $request);
            }
        }

        try {
            $profile = StudentTransportProfile::updateOrCreate(
                ['student_id' => $student->id],
                array_merge(['school_id' => $student->school_id], $validator->validated())
            );

            AuditLogger::log(
                'transportation.student_profile.updated',
                StudentTransportProfile::class,
                $profile->id,
                "Transportation profile updated for student {$student->id}"
            );

            return $this->ok($profile->load('morningRoute', 'afternoonRoute'), 'Transportation profile saved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error saving transportation profile', 500, $e->getMessage());
        }
    }
}
