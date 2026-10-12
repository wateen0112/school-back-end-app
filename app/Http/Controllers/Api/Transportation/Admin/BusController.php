<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Transportation\Bus;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusController extends TransportationController
{
    public function index(Request $request)
    {
        try {
            $query = $this->scopeToSchool(Bus::with('branch'), $request)
                ->orderBy('bus_number');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('bus_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('license_plate', 'like', "%{$search}%");
                });
            }

            return $this->ok($query->paginate($request->integer('per_page', 15)), 'Buses retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving buses', 500, $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $bus = Bus::with(['branch', 'routes', 'driverProfile.user'])->findOrFail($id);
        $this->assertSchool($bus->school_id, $request);

        return $this->ok($bus, 'Bus retrieved successfully');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bus_number' => 'required|string|max:50',
            'name' => 'nullable|string|max:100',
            'license_plate' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:0|max:200',
            'branch_id' => 'nullable|exists:school_branches,id',
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
            $bus = Bus::create([
                'school_id' => $schoolId,
                'branch_id' => $request->branch_id,
                'bus_number' => $request->bus_number,
                'name' => $request->name,
                'license_plate' => $request->license_plate,
                'capacity' => $request->capacity ?? 0,
                'status' => 'AVAILABLE',
                'notes' => $request->notes,
                'is_active' => true,
            ]);

            AuditLogger::log('transportation.bus.created', Bus::class, $bus->id, "Bus {$bus->bus_number} created");

            return $this->ok($bus, 'Bus created successfully', 201);
        } catch (\Exception $e) {
            return $this->fail('Error creating bus', 500, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $bus = Bus::findOrFail($id);
        $this->assertSchool($bus->school_id, $request);

        $validator = Validator::make($request->all(), [
            'bus_number' => 'sometimes|required|string|max:50',
            'name' => 'nullable|string|max:100',
            'license_plate' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:0|max:200',
            'branch_id' => 'nullable|exists:school_branches,id',
            'status' => 'nullable|in:' . implode(',', Bus::STATUSES),
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return $this->fail('Validation error', 422, $validator->errors()->first());
        }

        try {
            $bus->update($validator->validated());

            AuditLogger::log('transportation.bus.updated', Bus::class, $bus->id, "Bus {$bus->bus_number} updated");

            return $this->ok($bus->fresh(), 'Bus updated successfully');
        } catch (\Exception $e) {
            return $this->fail('Error updating bus', 500, $e->getMessage());
        }
    }

    /** Soft delete (archive) — hard delete only when the school never used it. */
    public function destroy(Request $request, $id)
    {
        $bus = Bus::findOrFail($id);
        $this->assertSchool($bus->school_id, $request);

        try {
            if ($bus->trips()->exists()) {
                $bus->update(['is_active' => false, 'status' => 'INACTIVE']);
                AuditLogger::log('transportation.bus.archived', Bus::class, $bus->id, "Bus {$bus->bus_number} archived (has trip history)");
                return $this->ok(null, 'Bus archived (kept for trip history)');
            }

            $bus->delete();
            AuditLogger::log('transportation.bus.deleted', Bus::class, $bus->id, "Bus {$bus->bus_number} deleted");

            return $this->ok(null, 'Bus deleted successfully');
        } catch (\Exception $e) {
            return $this->fail('Error deleting bus', 500, $e->getMessage());
        }
    }
}
