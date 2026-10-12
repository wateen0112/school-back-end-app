<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Models\Transportation\Bus;
use App\Models\Transportation\DriverProfile;
use App\Models\Transportation\TransportationTrip;
use App\Models\Transportation\TripStudent;
use Illuminate\Http\Request;

class DashboardController extends TransportationController
{
    /** Overview stats for the Transportation dashboard landing page. */
    public function overview(Request $request)
    {
        try {
            $schoolId = $this->schoolId($request);

            $base = fn (string $model) => $this->scopeToSchool($model::query(), $request);

            $activeTrips = (clone $base(TransportationTrip::class))
                ->whereIn('status', ['IN_PROGRESS', 'EMERGENCY'])
                ->count();

            $todayTrips = (clone $base(TransportationTrip::class))
                ->whereDate('trip_date', today())
                ->count();

            $scheduledToday = (clone $base(TransportationTrip::class))
                ->whereDate('trip_date', today())
                ->whereIn('status', ['SCHEDULED', 'READY'])
                ->count();

            $driversOnTrips = (clone $base(TransportationTrip::class))
                ->whereIn('status', ['IN_PROGRESS', 'EMERGENCY'])
                ->distinct('driver_user_id')
                ->count('driver_user_id');

            $studentsInTransit = TripStudent::query()
                ->where('status', 'PICKED_UP')
                ->whereHas('trip', function ($q) use ($schoolId) {
                    $q->whereIn('status', ['IN_PROGRESS', 'EMERGENCY']);
                    if ($schoolId) {
                        $q->where('school_id', $schoolId);
                    }
                })
                ->count();

            $emergencies = (clone $base(TransportationTrip::class))
                ->where('status', 'EMERGENCY')
                ->count();

            $offlineBuses = (clone $base(Bus::class))
                ->where('is_active', true)
                ->whereIn('status', ['OFFLINE', 'MAINTENANCE'])
                ->count();

            return $this->ok([
                'buses_total' => (clone $base(Bus::class))->where('is_active', true)->count(),
                'buses_on_trip' => (clone $base(Bus::class))->where('status', 'ON_TRIP')->count(),
                'drivers_total' => (clone $base(DriverProfile::class))->where('status', 'ACTIVE')->count(),
                'active_trips' => $activeTrips,
                'today_trips' => $todayTrips,
                'scheduled_today' => $scheduledToday,
                'drivers_on_trips' => $driversOnTrips,
                'students_in_transit' => $studentsInTransit,
                'emergency_alerts' => $emergencies,
                'offline_buses' => $offlineBuses,
            ], 'Transportation overview retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Error retrieving transportation overview', 500, $e->getMessage());
        }
    }
}
