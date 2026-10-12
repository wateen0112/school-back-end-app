<?php

namespace App\Models\Transportation;

use App\Models\School;
use App\Models\SchoolBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bus extends Model
{
    use SoftDeletes;

    protected $table = 'transportation_buses';

    protected $fillable = [
        'school_id', 'branch_id', 'bus_number', 'name', 'license_plate',
        'capacity', 'status', 'notes', 'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public const STATUSES = ['AVAILABLE', 'ASSIGNED', 'ON_TRIP', 'OFFLINE', 'MAINTENANCE', 'INACTIVE'];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function branch()
    {
        return $this->belongsTo(SchoolBranch::class, 'branch_id');
    }

    public function routes()
    {
        return $this->hasMany(TransportationRoute::class, 'bus_id');
    }

    public function trips()
    {
        return $this->hasMany(TransportationTrip::class, 'bus_id');
    }

    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class, 'assigned_bus_id');
    }
}
