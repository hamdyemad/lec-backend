<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNearbyDrivers(Request $request)
    {
        $maximum_distance = 10000000000; // 10 Killo Meter
        $rules = [
            'marketLat' => ['required'],
            'marketLng' => ['required'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        $marketLat = $request->marketLat;
        $marketLng = $request->marketLng;
        // Fetch drivers within 10km

        $drivers = DB::table('rc_users')
            ->selectRaw("
                id, username, image,email,dateOfBirth,mobile_code,mobile,gender,latitude, longitude,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance
            ", [$marketLat, $marketLng, $marketLat])
            ->where('type', '3')
            ->where('status', '1')
            ->having('distance', '<=', $maximum_distance)
            ->orderBy('distance', 'asc')
            ->get();

        return $this->sendRes(__('drivers.list'), true, $drivers);
    }
    public function track_location(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user->track_location()->create([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return $this->sendRes(__('locations.store'), true);
    }



}
