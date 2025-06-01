<?php

namespace App\Traits;

use App\Models\UserLocation;
use Illuminate\Support\Facades\DB;

trait Location
{

    function haversine($lat1, $lon1, $lat2, $lon2) {
        // return $earthRadius * $c; // Returns the distance in kilometers
        $earthRadius = 6371.0;

        // Convert latitude and longitude from degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Differences in coordinates
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        // Haversine formula
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Distance in kilometers
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    function getNearestLocationFromOneSeller($location_id, $seller_id) {
        $location = UserLocation::find($location_id);
        if($location) {
            $currentLat = $location->latitude;
            $currentLng = $location->longitude;

            // Get nearest location using Haversine formula
            $nearestLocation = UserLocation::select('id', 'user_id', 'name', 'latitude', 'longitude', DB::raw("
                (6371 * ACOS(
                    COS(RADIANS(" . $currentLat . ")) * COS(RADIANS(latitude)) *
                    COS(RADIANS(longitude) - RADIANS(" . $currentLng . ")) +
                    SIN(RADIANS(" . $currentLat . ")) * SIN(RADIANS(latitude))
                )) AS distance
            "))
            ->where('id', '!=', $location->id)  // Exclude the current location
            ->where('user_id', $seller_id)
            ->orderBy('distance', 'asc')
            ->first();

            if ($nearestLocation) {
                return $nearestLocation;
            } else {
                return null;
            }
        }
    }


    // Get the nearest seller from a given location between all sellers locations
    public function getNearestSellerLocation($client_location, $sellers_ids, $next = null)
    {
        $distances = [];
        $sellers_locations = UserLocation::whereIn('user_id', $sellers_ids)->get();

        foreach ($sellers_locations as $seller_location) {
            if($next) {
                if(
                    $seller_location['user_id'] != $next['user_id']
                    && $seller_location['id'] != $next['id']
                ) {
                    $distance = $this->haversine(
                        $next['latitude'],
                        $next['longitude'],
                        $seller_location['latitude'],
                        $seller_location['longitude']
                    );
                    $distances[] = [
                        'distance' => $distance,
                        'location' => $seller_location
                    ];
                }

            } else {
                $distance = $this->haversine(
                    $client_location['latitude'],
                    $client_location['longitude'],
                    $seller_location['latitude'],
                    $seller_location['longitude']
                );
                $distances[] = [
                    'distance' => $distance,
                    'location' => $seller_location
                ];
            }
        }
        if($distances) {
            // Sort sellers by distance (ascending order)
            usort($distances, fn($a, $b) => $a['distance'] <=> $b['distance']);
            return $distances[0];
        }
    }



    function get_nearest_locations() {
        $latitude = 37.7749;  // Replace with your current latitude
        $longitude = -122.4194; // Replace with your current longitude
        $radius = 3; // 3 km radius

        $locations = DB::table("users_locations")
            ->select(
                "locations.*",
                DB::raw("(
                    6371 * acos(
                        cos(radians($latitude))
                        * cos(radians(locations.latitude))
                        * cos(radians(locations.longitude) - radians($longitude))
                        + sin(radians($latitude))
                        * sin(radians(locations.latitude))
                    )
                ) AS distance")
            )
            ->having("distance", "<=", $radius)
            ->orderBy("distance")
            ->get();

        return $locations;
    }



}
