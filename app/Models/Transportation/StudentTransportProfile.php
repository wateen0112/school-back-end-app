<?php

namespace App\Models\Transportation;

use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

/**
 * Transportation-only extension of the existing student entity.
 * The student record itself is never duplicated or modified.
 */
class StudentTransportProfile extends Model
{
    protected $table = 'transportation_student_profiles';

    protected $fillable = [
        'school_id', 'student_id', 'is_enabled',
        'morning_route_id', 'afternoon_route_id',
        'morning_pickup_address', 'morning_pickup_lat', 'morning_pickup_lng', 'morning_pickup_time',
        'morning_dropoff_address', 'morning_dropoff_lat', 'morning_dropoff_lng', 'morning_dropoff_time',
        'afternoon_pickup_address', 'afternoon_pickup_lat', 'afternoon_pickup_lng', 'afternoon_pickup_time',
        'afternoon_dropoff_address', 'afternoon_dropoff_lat', 'afternoon_dropoff_lng', 'afternoon_dropoff_time',
        'notes',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'morning_pickup_lat' => 'float', 'morning_pickup_lng' => 'float',
        'morning_dropoff_lat' => 'float', 'morning_dropoff_lng' => 'float',
        'afternoon_pickup_lat' => 'float', 'afternoon_pickup_lng' => 'float',
        'afternoon_dropoff_lat' => 'float', 'afternoon_dropoff_lng' => 'float',
        'morning_pickup_time' => 'datetime:H:i',
        'morning_dropoff_time' => 'datetime:H:i',
        'afternoon_pickup_time' => 'datetime:H:i',
        'afternoon_dropoff_time' => 'datetime:H:i',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function morningRoute()
    {
        return $this->belongsTo(TransportationRoute::class, 'morning_route_id');
    }

    public function afternoonRoute()
    {
        return $this->belongsTo(TransportationRoute::class, 'afternoon_route_id');
    }

    /**
     * Pickup/dropoff snapshot for a direction, used when building trips.
     *
     * @return array{address: ?string, lat: ?float, lng: ?float}
     */
    public function pickupFor(string $direction): array
    {
        $prefix = $direction === 'SCHOOL_TO_HOME' ? 'afternoon' : 'morning';

        return [
            'address' => $this->getAttribute($prefix . '_pickup_address'),
            'lat' => $this->getAttribute($prefix . '_pickup_lat'),
            'lng' => $this->getAttribute($prefix . '_pickup_lng'),
        ];
    }

    public function dropoffFor(string $direction): array
    {
        $prefix = $direction === 'SCHOOL_TO_HOME' ? 'afternoon' : 'morning';

        return [
            'address' => $this->getAttribute($prefix . '_dropoff_address'),
            'lat' => $this->getAttribute($prefix . '_dropoff_lat'),
            'lng' => $this->getAttribute($prefix . '_dropoff_lng'),
        ];
    }
}
