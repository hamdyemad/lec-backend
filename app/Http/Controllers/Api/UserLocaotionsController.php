<?php

namespace App\Http\Controllers\Api;

use App\Events\TrackLocationEvent;
use App\Http\Controllers\Controller;
use App\Models\TrackLocation;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Location;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserLocaotionsController extends Controller
{
    use Res, FileUploads, Location;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $paginate = $request->paginate;

        $rules = [
            'paginate' => ['nullable', 'integer'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = auth()->user();
        if($user) {
            if(isset($user->locations)) {
                $locations = $user->locations()->latest();
                if($request->name) {
                    $locations = $locations->where('name', 'like', "%$request->name%");
                }
                if($request->latitude) {
                    $locations = $locations->where('latitude', 'like', "%$request->latitude%");
                }

                if($request->longitude) {
                    $locations = $locations->where('longitude', 'like', "%$request->longitude%");
                }
                if($paginate) {
                    $locations = $locations->paginate($paginate);
                } else {
                    $locations = $locations->get();
                }
                return $this->sendRes(__('locations.list'), true, $locations);
            } else {
                return $this->sendRes(__('locations.not found'), true);
            }
        } else {
            return $this->sendRes(__('auth.user not found'), true);
        }
    }


    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required','numeric'],
            'longitude' => ['required','numeric'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }


        $user = auth()->user();
        $location = $user->locations()->create([
            'name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        return $this->sendRes(__('locations.store'), true, $location);
    }

    public function edit(Request $request, $location_id)
    {
        $user = auth()->user();
        $finded_location = $user->locations()->find($location_id);
        if($finded_location) {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'latitude' => ['required','numeric'],
                'longitude' => ['required','numeric'],
                'address' => ['required', 'string', 'max:255'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                $message = implode('<br>', $validator->errors()->all());
                return $this->sendRes($message, false, [], $validator->errors(), 400);
            }


            $user = auth()->user();
            $finded_location->update([
                'name' => $request->name,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'address' => $request->address,
            ]);

            return $this->sendRes(__('locations.update'), true, $finded_location);

        } else {
            return $this->sendRes(__('locations.not found'), false, [], [], 400);
        }
    }


    public function delete(Request $request, $location_id)
    {
        $user = auth()->user();
        $finded_location = $user->locations()->find($location_id);
        if($finded_location) {
            $finded_location->delete();
            return $this->sendRes(__('locations.delete'), true);
        } else {
            return $this->sendRes(__('locations.not found'), false, [], [], 400);
        }
    }


    public function distance(Request $request)
    {
        $rules = [
            'client_lat' => ['required'],
            'client_lng' => ['required'],
            'place_lng' => ['required', 'array', 'max:2'],
            'place_lat' => ['required', 'array', 'max:2'],
        ];


        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            if(is_array($request['place_lng']) || is_array($request['place_lat'])) {
                $lngCount = count($request['place_lng'] ?? []);
                $latCount = count($request['place_lat'] ?? []);
                if ($lngCount !== $latCount) {
                    $validator->errors()->add('place_lng', 'الطول والعرض يجب ان يطابقوا بعض');
                    $validator->errors()->add('place_lat', 'الطول والعرض يجب ان يطابقوا بعض');
                }
            }
        });


        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $cost_min = settings("cost_min");
        $kilo_price = settings("kilo_price");
        $tax = settings("tax");


        $distances = [];
        if(isset($request->place_lat)) {
            for ($i=0; $i <= count($request->place_lat); $i++) {
                if(isset($request->place_lat[$i])) {
                    $distance_between_client_and_place = $this->haversine($request->client_lat, $request->client_lng, $request->place_lat[$i], $request->place_lng[$i]);
                    if($distance_between_client_and_place < 3) {
                        $total_kilo_price = $cost_min;
                    } else {
                        $diff_3_killos = $distance_between_client_and_place - 3;
                        $total_kilo_price = $cost_min + ($kilo_price * $diff_3_killos);
                    }


                    $distances[$i]['place_lng'] = $request->place_lng[$i];
                    $distances[$i]['place_lat'] = $request->place_lat[$i];
                    $distances[$i]['distance_between_client_and_place'] = round($distance_between_client_and_place, 2);
                    $distances[$i]['total_kilo_price'] = round($total_kilo_price, 2);
                    $distances[$i]['tax'] = (float) $tax;
                    $total = $total_kilo_price + ($total_kilo_price * ($tax / 100));
                    $distances[$i]['total'] = round($total, 2);
                }
            }
        }

        $data = [
            'distances' => $distances,
            'total_distances' => [
                'distance_between_client_and_place' => array_sum(array_column($distances, 'distance_between_client_and_place')),
                'total_kilo_price' => array_sum(array_column($distances, 'total_kilo_price')),
                'tax' => (float) $tax,
                'total' => array_sum(array_column($distances, 'total')),
            ]
        ];

        return $this->sendRes(__('validation.success'), true, $data);
    }



    public function tracking(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'order_id' => ['required', 'exists:orders,id'],
            'latitude' => ['required'],
            'longitude' => ['required'],
        ];


        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        if(
            $user->type == 3 && $user->driver_orders->contains($request->order_id)
            ||
            $user->type == 1 && $user->orders->contains($request->order_id)
            ) {

                // Assuming you're passing the user id and type (driver or customer)
                $tracking_location = TrackLocation::create([
                    'user_id' => auth()->id(),
                    'order_id' => $request->order_id,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude
                ]);

                broadcast(new TrackLocationEvent($tracking_location));
                return $this->sendRes(__('validation.success'), true, $tracking_location);

        }  else {
            return $this->sendRes(__('orders.not found'), false, [], [], 400);
        }






    }



}
