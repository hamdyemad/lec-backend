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

class LawyersController extends Controller
{
    use Res, FileUploads;
    protected $user_type_id = 2;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $lawyers = User::with('country', 'city')->latest()->where('user_type_id', $this->user_type_id);
        $per_page = $request->get('per_page', 12);
        $keyword = $request->get('keyword', '');
        $city_id = $request->get('city_id', '');


        if($keyword) {
            $lawyers = $lawyers
            ->where('name', 'like', "%$keyword%")
            ->orWhere('national_id', 'like', "%$keyword%")
            ->orWhere('address', 'like', "%$keyword%")
            ->orWhere('phone_code', 'like', "%$keyword%")
            ->orWhere('phone', 'like', "%$keyword%")
            ->orWhere('email', 'like', "%$keyword%");
        }

        if($city_id) {
            $lawyers = $lawyers->whereHas('city', function($city) use($city_id) {
                $city->where('id', $city_id);
            });
        }

        $lawyers = $lawyers->paginate($per_page);
        $lawyers->getCollection()->transform(function($lawyer) {
            return new UserResource($lawyer);
        });

        return $this->sendRes('all lawyers', true, $lawyers);
    }


    public function form(Request $request, $lawyer = null) {

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($lawyer ?? null), 'string', 'max:255'],
            'phone_code' => ['required', 'string', 'exists:countries,call_key'],
            'phone' => ['required', 'string'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string','min:8', 'confirmed'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'phone_code' => $request->phone_code,
            'password' => Hash::make($request->password),
            'country_id' => $request->country_id,
            'city_id' => $request->city_id,
            'national_id' => $request->national_id,
        ];
        if($lawyer) {
            $lawyer->update($data);
            $message = translate('lawyer has been updated successfully !');
        } else {
            $data['uuid'] = \Str::uuid();
            $data['user_type_id'] = $this->user_type_id;
            $data['active'] = true;
            $lawyer = User::create($data);
            $message = translate('lawyer has been created successfully !');
        }
        return $this->sendRes($message, true, [], [], 200);

    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $lawyer = User::where('uuid', $uuid)->first();
        if(!$lawyer) {
            return $this->sendRes(translate('lawyer not found'), false, [], [], 400);
        }
        return $this->form($request, $lawyer);

    }


    public function show(Request $request, $uuid) {
        $lawyer = User::with('country', 'city')->where('uuid', $uuid)->first();
        if(!$lawyer) {
            return $this->sendRes(translate('lawyer not found'), false, [], [], 400);
        }
        $lawyer = new UserResource($lawyer);
        return $this->sendRes(translate('lawyer found'), true, $lawyer);
    }


    public function delete(Request $request, $uuid) {
        $lawyer = User::where('uuid', $uuid)->first();
        if(!$lawyer) {
            return $this->sendRes(translate('lawyer not found'), false, [], [], 400);
        }
        $lawyer->delete();
        return $this->sendRes(translate('lawyer deleted successfully'), true, [], [], 200);
    }




}
