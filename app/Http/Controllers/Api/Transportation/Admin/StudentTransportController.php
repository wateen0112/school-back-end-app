<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\Transportation\StudentTransportProfile;
use App\Services\AuditLogger;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                Student::with([
                    'transportProfile.morningRoute', 'transportProfile.afternoonRoute',
                    'transportProfile.classroom', 'transportProfile.academicYear',
                    'parent', 'grade',
                ])
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
            if ($request->filled('academic_year_id')) {
                $yearId = $request->academic_year_id;
                $query->whereHas('transportProfile', fn ($q) => $q->where('academic_year_id', $yearId));
            }
            if ($request->filled('classroom_id')) {
                $classroomId = $request->classroom_id;
                $query->whereHas('transportProfile', fn ($q) => $q->where('classroom_id', $classroomId));
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
                Student::select('id', 'name', 'school_id', 'parent_id', 'Grade_id', 'Classroom_id', 'section_id', 'residence_address', 'home_lat', 'home_lng')
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

    /** Store the student's home location on the student record itself. */
    public function updateHomeLocation(Request $request, $studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            $this->assertSchool($student->school_id, $request);

            $validator = Validator::make($request->all(), [
                'residence_address' => 'nullable|string|max:255',
                'home_lat' => 'nullable|numeric|between:-90,90',
                'home_lng' => 'nullable|numeric|between:-180,180',
            ]);
            if ($validator->fails()) {
                return $this->fail('Validation error', 422, $validator->errors()->first());
            }

            $data = $validator->validated();
            if ($request->has('residence_address')) {
                $student->residence_address = $data['residence_address'] ?? null;
            }
            if ($request->has('home_lat')) {
                $student->home_lat = $data['home_lat'] ?? null;
            }
            if ($request->has('home_lng')) {
                $student->home_lng = $data['home_lng'] ?? null;
            }
            $student->save();

            return $this->ok($student, 'Home location saved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error saving home location', 500, $e->getMessage());
        }
    }

    /** Create or update the transportation profile (assignments + coordinates). */
    public function upsert(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);
        $this->assertSchool($student->school_id, $request);

        $existing = StudentTransportProfile::where('student_id', $student->id)->first();
        $isEnabled = $request->has('is_enabled')
            ? $request->boolean('is_enabled')
            : (bool) ($existing?->is_enabled ?? true);

        $validator = Validator::make($request->all(), [
            'is_enabled' => 'nullable|boolean',
            'agreed_amount' => 'nullable|numeric|min:0|max:99999999.99',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'classroom_id' => 'nullable|exists:Classrooms,id',
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

        $data = $validator->validated();

        // An enabled assignment must always carry the agreed amount.
        $amount = $request->has('agreed_amount') ? $request->agreed_amount : ($existing?->agreed_amount ?? null);
        if ($isEnabled && ($amount === null || $amount === '')) {
            return $this->fail('Validation error', 422, 'agreed_amount is required when transportation is enabled');
        }
        if ($isEnabled && ! is_null($amount)) {
            $data['agreed_amount'] = round((float) $amount, 2);
        }

        // Defaults come from the student's own enrollment when not sent.
        if (! $request->filled('academic_year_id')) {
            $data['academic_year_id'] = $student->academic_year_id;
        }
        if (! $request->filled('classroom_id')) {
            $data['classroom_id'] = $student->Classroom_id;
        }

        // An enabled assignment must also be tied to a year and a classroom.
        if ($isEnabled && empty($data['academic_year_id'])) {
            return $this->fail('Validation error', 422, 'academic_year_id is required when transportation is enabled');
        }
        if ($isEnabled && empty($data['classroom_id'])) {
            return $this->fail('Validation error', 422, 'classroom_id is required when transportation is enabled');
        }

        // Route assignments must reference routes of the same school.
        foreach (['morning_route_id', 'afternoon_route_id'] as $field) {
            if ($request->filled($field)) {
                $route = \App\Models\Transportation\TransportationRoute::find($request->$field);
                $this->assertSchool($route?->school_id, $request);
            }
        }

        try {
            $profile = DB::transaction(function () use ($student, $data) {
                $profile = StudentTransportProfile::updateOrCreate(
                    ['student_id' => $student->id],
                    array_merge(['school_id' => $student->school_id], $data)
                );

                $this->syncTransportAccount($profile);

                return $profile;
            });

            CacheVersion::bump('accounting');

            AuditLogger::log(
                'transportation.student_profile.updated',
                StudentTransportProfile::class,
                $profile->id,
                "Transportation profile updated for student {$student->id}"
            );

            return $this->ok(
                $profile->load(['morningRoute', 'afternoonRoute', 'classroom', 'academicYear']),
                'Transportation profile saved successfully'
            );
        } catch (\Exception $e) {
            return $this->fail('Error saving transportation profile', 500, $e->getMessage());
        }
    }

    /** Totals of agreed transportation amounts (charges + breakdowns). */
    public function finance(Request $request)
    {
        try {
            $profiles = $this->scopeToSchool(
                StudentTransportProfile::with(['classroom', 'academicYear', 'student:id,name'])
                    ->where('is_enabled', true),
                $request
            )->get();

            $charged = StudentAccount::where('type', 'transport')
                ->whereIn('transport_profile_id', $profiles->pluck('id'))
                ->sum('Debit');

            $breakdown = function (string $key, string $relation, string $nameAttr) use ($profiles) {
                return $profiles
                    ->filter(fn ($p) => $p->agreed_amount !== null && $p->getAttribute($key) !== null)
                    ->groupBy(fn ($p) => (string) $p->getAttribute($key))
                    ->map(function ($group, $id) use ($key, $relation, $nameAttr) {
                        $first = $group->first();

                        return [
                            $key => (int) $id,
                            'name' => $first->getAttribute($relation)?->getAttribute($nameAttr),
                            'students_count' => $group->count(),
                            'total_agreed' => round($group->sum(fn ($p) => (float) $p->agreed_amount), 2),
                        ];
                    })
                    ->values();
            };

            return $this->ok([
                'students_count' => $profiles->count(),
                'with_amount_count' => $profiles->filter(fn ($p) => $p->agreed_amount !== null)->count(),
                'total_agreed' => round($profiles->sum(fn ($p) => (float) ($p->agreed_amount ?? 0)), 2),
                'total_charged' => round((float) $charged, 2),
                'by_academic_year' => $breakdown('academic_year_id', 'academicYear', 'name'),
                'by_classroom' => $breakdown('classroom_id', 'classroom', 'Name_Class'),
            ], 'Transportation finance retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving transportation finance', 500, $e->getMessage());
        }
    }

    /**
     * Keep the accounting entry (type = "transport") in sync with the
     * assignment: create/update the charge, or drop it when the profile is
     * disabled or the amount is cleared. The entry's date is fixed at creation.
     */
    private function syncTransportAccount(StudentTransportProfile $profile): void
    {
        $entry = StudentAccount::where('transport_profile_id', $profile->id)->first();

        $chargeable = $profile->is_enabled
            && $profile->agreed_amount !== null
            && (float) $profile->agreed_amount > 0;

        if (! $chargeable) {
            $entry?->delete();

            return;
        }

        if (! $entry) {
            $entry = new StudentAccount();
            $entry->date = date('Y-m-d');
            $entry->type = 'transport';
            $entry->transport_profile_id = $profile->id;
            $entry->student_id = $profile->student_id;
        }

        $entry->Debit = round((float) $profile->agreed_amount, 2);
        $entry->credit = 0.00;
        $entry->description = 'رسوم مواصلات';
        $entry->save();
    }
}
