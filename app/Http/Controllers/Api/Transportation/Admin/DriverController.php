<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Role;
use App\Models\Transportation\DriverProfile;
use App\Services\AuditLogger;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Drivers are existing system users (App\User) + a "driver" school role.
 * No separate authentication is introduced.
 */
class DriverController extends TransportationController
{
    public function index(Request $request)
    {
        try {
            $query = $this->scopeToSchool(
                DriverProfile::with(['user', 'assignedBus'])->orderByDesc('id'),
                $request
            );

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            }

            return $this->ok($query->paginate($request->integer('per_page', 15)), 'Drivers retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving drivers', 500, $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $driver = DriverProfile::with(['user', 'assignedBus', 'routes.bus'])->findOrFail($id);
        $this->assertSchool($driver->school_id, $request);

        $driver->setRelation('currentTrip', $driver->trips()
            ->whereIn('status', ['SCHEDULED', 'READY', 'IN_PROGRESS', 'EMERGENCY'])
            ->with('route', 'bus')
            ->latest('trip_date')
            ->first());

        return $this->ok($driver, 'Driver retrieved successfully');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6|max:100',
            'phone' => 'nullable|string|max:30',
            'employee_no' => 'nullable|string|max:50',
            'license_no' => 'nullable|string|max:50',
            'assigned_bus_id' => 'nullable|exists:transportation_buses,id',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        $schoolId = $this->schoolId($request);
        if (! $schoolId) {
            return $this->fail('A school context is required', 400);
        }

        try {
            $driver = DB::transaction(function () use ($request, $schoolId) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'school_id' => $schoolId,
                ]);

                $this->attachDriverRole($user, $schoolId);

                return DriverProfile::create([
                    'school_id' => $schoolId,
                    'user_id' => $user->id,
                    'employee_no' => $request->employee_no,
                    'phone' => $request->phone,
                    'license_no' => $request->license_no,
                    'assigned_bus_id' => $request->assigned_bus_id,
                    'status' => 'ACTIVE',
                    'notes' => $request->notes,
                ]);
            });

            AuditLogger::log('transportation.driver.created', DriverProfile::class, $driver->id, "Driver {$request->email} created");

            return $this->ok($driver->load('user'), 'Driver created successfully', 201);
        } catch (\Exception $e) {
            return $this->fail('Error creating driver', 500, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $driver = DriverProfile::with('user')->findOrFail($id);
        $this->assertSchool($driver->school_id, $request);

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'employee_no' => 'nullable|string|max:50',
            'license_no' => 'nullable|string|max:50',
            'assigned_bus_id' => 'nullable|exists:transportation_buses,id',
            'status' => 'nullable|in:ACTIVE,INACTIVE',
            'notes' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:6|max:100',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            DB::transaction(function () use ($request, $driver) {
                if ($request->filled('name') || $request->filled('password')) {
                    $userData = [];
                    if ($request->filled('name')) {
                        $userData['name'] = $request->name;
                    }
                    if ($request->filled('password')) {
                        $userData['password'] = Hash::make($request->password);
                    }
                    $driver->user->update($userData);
                }

                $driver->update(collect($validator->validated())
                    ->only(['phone', 'employee_no', 'license_no', 'assigned_bus_id', 'status', 'notes'])
                    ->toArray());
            });

            AuditLogger::log('transportation.driver.updated', DriverProfile::class, $driver->id, "Driver {$driver->user->email} updated");

            return $this->ok($driver->fresh()->load('user', 'assignedBus'), 'Driver updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating driver', 500, $e->getMessage());
        }
    }

    /** Attach (and lazily create) the school-scoped "driver" role. */
    protected function attachDriverRole(User $user, int $schoolId): void
    {
        $role = Role::where('school_id', $schoolId)
            ->where(fn ($q) => $q->where('name', 'driver')->orWhere('name->en', 'driver'))
            ->first();

        if (! $role) {
            $role = Role::create([
                'name' => ['en' => 'Driver', 'ar' => 'سائق'],
                'description' => 'School bus driver — mobile app access to assigned trips only',
                'permissions' => [],
                'is_active' => true,
                'school_id' => $schoolId,
            ]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
