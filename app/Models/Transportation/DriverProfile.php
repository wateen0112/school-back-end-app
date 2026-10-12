<?php

namespace App\Models\Transportation;

use App\Models\School;
use App\User;
use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    protected $table = 'transportation_driver_profiles';

    protected $fillable = [
        'school_id', 'user_id', 'employee_no', 'phone', 'license_no',
        'assigned_bus_id', 'status', 'notes',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /** The driver is an existing system user holding the "driver" role. */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBus()
    {
        return $this->belongsTo(Bus::class, 'assigned_bus_id');
    }

    public function routes()
    {
        return $this->hasMany(TransportationRoute::class, 'driver_user_id', 'user_id');
    }

    public function trips()
    {
        return $this->hasMany(TransportationTrip::class, 'driver_user_id', 'user_id');
    }
}
