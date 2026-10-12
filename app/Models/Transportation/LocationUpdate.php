<?php

namespace App\Models\Transportation;

use App\User;
use Illuminate\Database\Eloquent\Model;

class LocationUpdate extends Model
{
    protected $table = 'transportation_location_updates';

    protected $fillable = [
        'trip_id', 'bus_id', 'driver_user_id',
        'latitude', 'longitude', 'accuracy', 'speed', 'heading', 'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float', 'longitude' => 'float',
        'accuracy' => 'float', 'speed' => 'float', 'heading' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(TransportationTrip::class, 'trip_id');
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }
}
