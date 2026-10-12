<?php

namespace App\Http\Controllers\Api\Transportation\Admin;

use App\Http\Controllers\Api\Transportation\TransportationController;
use App\Services\Transportation\MapService;
use Illuminate\Http\Request;

/** Server-side proxy to the configured map provider (no keys exposed to the browser). */
class GeocodeController extends TransportationController
{
    public function geocode(Request $request)
    {
        $request->validate(['q' => 'required|string|max:255']);

        $provider = MapService::forSchool($this->schoolId($request) ?? 0);

        $result = $provider->geocodeAddress($request->q);

        if (! $result) {
            return $this->fail('Address not found', 404);
        }

        return $this->ok($result, 'Address geocoded successfully');
    }

    public function reverse(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $provider = MapService::forSchool($this->schoolId($request) ?? 0);

        $address = $provider->reverseGeocode((float) $request->lat, (float) $request->lng);

        return $this->ok(['address' => $address], 'Coordinates resolved successfully');
    }
}
