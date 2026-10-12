<?php

namespace App\Models\Transportation;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;

class TransportationSetting extends Model
{
    protected $table = 'transportation_settings';

    protected $fillable = [
        'school_id', 'geofence_radius_m', 'gps_interval_s',
        'parent_live_tracking', 'approaching_notifications',
        'notify_parents_on_emergency', 'morning_default_start', 'afternoon_default_start',
        'working_days', 'map_provider',
    ];

    protected $casts = [
        'geofence_radius_m' => 'integer',
        'gps_interval_s' => 'integer',
        'parent_live_tracking' => 'boolean',
        'approaching_notifications' => 'boolean',
        'notify_parents_on_emergency' => 'boolean',
        'working_days' => 'array',
        'morning_default_start' => 'datetime:H:i',
        'afternoon_default_start' => 'datetime:H:i',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /** Settings for a school, creating defaults on first access. */
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(['school_id' => $schoolId], [
            'geofence_radius_m' => 150,
            'gps_interval_s' => 10,
            'parent_live_tracking' => true,
            'approaching_notifications' => true,
            'notify_parents_on_emergency' => true,
            'map_provider' => 'osm',
        ]);
    }
}
