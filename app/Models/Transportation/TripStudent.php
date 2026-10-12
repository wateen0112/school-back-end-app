<?php

namespace App\Models\Transportation;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class TripStudent extends Model
{
    protected $table = 'transportation_trip_students';

    protected $fillable = [
        'trip_id', 'student_id', 'stop_order',
        'pickup_address', 'pickup_lat', 'pickup_lng',
        'dropoff_address', 'dropoff_lat', 'dropoff_lng',
        'status', 'approaching_pickup_sent', 'approaching_dropoff_sent',
        'picked_up_at', 'picked_up_lat', 'picked_up_lng',
        'dropped_off_at', 'dropped_off_lat', 'dropped_off_lng',
        'client_event_id',
    ];

    protected $casts = [
        'stop_order' => 'integer',
        'pickup_lat' => 'float', 'pickup_lng' => 'float',
        'dropoff_lat' => 'float', 'dropoff_lng' => 'float',
        'picked_up_lat' => 'float', 'picked_up_lng' => 'float',
        'dropped_off_lat' => 'float', 'dropped_off_lng' => 'float',
        'approaching_pickup_sent' => 'boolean',
        'approaching_dropoff_sent' => 'boolean',
        'picked_up_at' => 'datetime',
        'dropped_off_at' => 'datetime',
    ];

    public const STATUSES = ['WAITING', 'PICKED_UP', 'DROPPED_OFF', 'ABSENT', 'CANCELLED'];

    public function trip()
    {
        return $this->belongsTo(TransportationTrip::class, 'trip_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /** The stop the geofence should measure against, based on trip direction. */
    public function pendingStop(): ?array
    {
        if ($this->status === 'WAITING') {
            return $this->pickup_lat !== null
                ? ['kind' => 'pickup', 'lat' => $this->pickup_lat, 'lng' => $this->pickup_lng]
                : null;
        }

        if ($this->status === 'PICKED_UP') {
            return $this->dropoff_lat !== null
                ? ['kind' => 'dropoff', 'lat' => $this->dropoff_lat, 'lng' => $this->dropoff_lng]
                : null;
        }

        return null;
    }
}
