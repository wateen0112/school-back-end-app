<?php

namespace App\Models\Transportation;

use App\Models\School;
use App\Models\SchoolBranch;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportationRoute extends Model
{
    use SoftDeletes;

    protected $table = 'transportation_routes';

    protected $fillable = [
        'school_id', 'branch_id', 'name', 'code', 'direction',
        'bus_id', 'driver_user_id', 'scheduled_start_time', 'active_days', 'is_active',
    ];

    protected $casts = [
        'active_days' => 'array',
        'is_active' => 'boolean',
        'scheduled_start_time' => 'datetime:H:i',
    ];

    public const DIRECTIONS = ['HOME_TO_SCHOOL', 'SCHOOL_TO_HOME'];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function branch()
    {
        return $this->belongsTo(SchoolBranch::class, 'branch_id');
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class, 'bus_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function stops()
    {
        return $this->hasMany(RouteStop::class, 'route_id')->orderBy('stop_order');
    }

    public function trips()
    {
        return $this->hasMany(TransportationTrip::class, 'route_id');
    }

    /** Students whose profile assigns them to this route for the route's direction. */
    public function studentProfiles()
    {
        return $this->direction === 'SCHOOL_TO_HOME'
            ? $this->hasMany(StudentTransportProfile::class, 'afternoon_route_id')
            : $this->hasMany(StudentTransportProfile::class, 'morning_route_id');
    }
}
