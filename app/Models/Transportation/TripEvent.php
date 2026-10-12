<?php

namespace App\Models\Transportation;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class TripEvent extends Model
{
    protected $table = 'transportation_trip_events';

    protected $fillable = [
        'school_id', 'trip_id', 'student_id', 'type',
        'latitude', 'longitude', 'meta', 'actor_id', 'actor_type',
    ];

    protected $casts = [
        'latitude' => 'float', 'longitude' => 'float',
        'meta' => 'array',
    ];

    public const TYPES = [
        'TRIP_STARTED', 'BUS_APPROACHING_PICKUP', 'STUDENT_PICKED_UP',
        'BUS_APPROACHING_DROPOFF', 'STUDENT_DROPPED_OFF',
        'TRIP_ENDED', 'TRIP_CANCELLED', 'EMERGENCY',
    ];

    public function trip()
    {
        return $this->belongsTo(TransportationTrip::class, 'trip_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function actor()
    {
        return $this->morphTo();
    }
}
