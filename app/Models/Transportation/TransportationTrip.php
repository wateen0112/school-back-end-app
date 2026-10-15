<?php

namespace App\Models\Transportation;

use App\Models\School;
use App\User;
use Illuminate\Database\Eloquent\Model;

class TransportationTrip extends Model
{
    protected $table = 'transportation_trips';

    protected $fillable = [
        'school_id', 'route_id', 'bus_id', 'driver_user_id', 'direction',
        'trip_date', 'scheduled_start_time', 'started_at', 'ended_at',
        'status', 'emergency_at', 'cancelled_reason', 'settings_snapshot', 'created_by',
    ];

    protected $casts = [
        'trip_date' => 'date:Y-m-d',
        'scheduled_start_time' => 'datetime:H:i',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'emergency_at' => 'datetime',
        'settings_snapshot' => 'array',
    ];

    public const STATUSES = ['SCHEDULED', 'READY', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'EMERGENCY'];

    public const ACTIVE_STATUSES = ['SCHEDULED', 'READY', 'IN_PROGRESS', 'EMERGENCY'];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function route()
    {
        return $this->belongsTo(TransportationRoute::class, 'route_id');
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function tripStudents()
    {
        return $this->hasMany(TripStudent::class, 'trip_id')->orderBy('stop_order');
    }

    public function locationUpdates()
    {
        return $this->hasMany(LocationUpdate::class, 'trip_id');
    }

    public function latestLocation()
    {
        return $this->hasOne(LocationUpdate::class, 'trip_id')->latestOfMany('recorded_at');
    }

    public function events()
    {
        return $this->hasMany(TripEvent::class, 'trip_id')->orderBy('created_at');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['IN_PROGRESS', 'EMERGENCY'], true);
    }
}
