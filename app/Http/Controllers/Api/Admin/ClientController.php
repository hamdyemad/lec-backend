<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    protected $user_type_id = 3;
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $clients = User::with('country', 'city')->latest()->where('user_type_id', $this->user_type_id);
        $per_page = $request->get('per_page', 12);

        $keyword = $request->get('keyword', '');
        $city_id = $request->get('city_id', '');
        if($keyword) {
            $clients = $clients
            ->where('name', 'like', "%$keyword%")
            ->orWhere('national_id', 'like', "%$keyword%")
            ->orWhere('address', 'like', "%$keyword%")
            ->orWhere('phone_code', 'like', "%$keyword%")
            ->orWhere('phone', 'like', "%$keyword%")
            ->orWhere('email', 'like', "%$keyword%");
        }

        if($city_id) {
            $clients = $clients->whereHas('city', function($city) use($city_id) {
                $city->where('id', $city_id);
            });
        }

        $clients = $clients->paginate($per_page);
        $clients->getCollection()->transform(function($user) {
            return new UserResource($user);
        });

        return $this->sendRes('all clients', true, $clients);
    }


    public function form(Request $request, $client = null) {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($client?->id), 'string', 'max:255'],
            'phone_code' => ['required', 'string', 'exists:countries,call_key'],
            'phone' => ['required', 'string'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['nullable', 'string', 'exists:cities,id'],
            'national_id' => ['nullable', 'string', 'max:255'],
        ];

        if(!$client) {
            $rules['password'] = ['required', 'string','min:8', 'confirmed'];
        }

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'phone_code' => $request->phone_code,
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'national_id' => $request->national_id,
        ];
        if($client) {
            $client->update($data);
            $message = translate('client has been updated successfully !');
        } else {
            $data['password'] = Hash::make($request->password);
            $data['uuid'] = \Str::uuid();
            $data['user_type_id'] = $this->user_type_id;
            $data['active'] = true;
            $client = User::create($data);
            $message = translate('client has been created successfully !');
        }
        return $this->sendRes($message, true, [], [], 200);

    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $client = User::where('uuid', $uuid)->first();
        if(!$client) {
            return $this->sendRes(translate('client not found'), false, [], [], 400);
        }
        return $this->form($request, $client);

    }


    public function show(Request $request, $uuid) {
        $client = User::with('country', 'city')->where('uuid', $uuid)->first();
        if(!$client) {
            return $this->sendRes(translate('client not found'), false, [], [], 400);
        }
        $client = new UserResource($client);
        return $this->sendRes(translate('client found'), true, $client);
    }


    public function delete(Request $request, $uuid) {
        $client = User::where('uuid', $uuid)->first();
        if(!$client) {
            return $this->sendRes(translate('client not found'), false, [], [], 400);
        }
        $client->delete();
        return $this->sendRes(translate('client deleted successfully'), true, [], [], 200);
    }




}
