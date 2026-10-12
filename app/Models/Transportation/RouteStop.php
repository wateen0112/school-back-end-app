<?php

namespace App\Models\Transportation;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    protected $table = 'transportation_route_stops';

    protected $fillable = ['route_id', 'student_id', 'stop_order'];

    protected $casts = ['stop_order' => 'integer'];

    public function route()
    {
        return $this->belongsTo(TransportationRoute::class, 'route_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
