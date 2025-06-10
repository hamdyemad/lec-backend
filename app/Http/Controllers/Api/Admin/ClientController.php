<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $select = ['id', 'uuid', 'name', 'username', 'email', 'mobile', 'mobile_code', 'status', 'created_at'];
        $employees = User::select($select)->latest()->where('user_type_id', 3);
        $per_page = $request->get('per_page', 12);
        $employees = $employees->paginate($per_page);

        return $this->sendRes('all clients', true, $employees);
    }


    public function form(Request $request, $employee = null) {

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('rc_users', 'username')->ignore($employee ? $employee->id : null)],
            'email' => ['required', 'email', 'string', 'max:255'],
            'mobile_code' => ['required', 'string', 'exists:countries,call_key'],
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string','min:8', 'confirmed'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'mobile_code' => $request->mobile_code,
            'password' => Hash::make($request->password),
        ];
        if($employee) {
            $employee->update($data);
            $message = translate('employee has been updated successfully !');
        } else {
            $data['uuid'] = \Str::uuid();
            $data['user_type_id'] = '2';
            $data['status'] = true;
            $employee = User::create($data);
            $message = translate('employee has been created successfully !');
        }
        return $this->sendRes($message, true, $employee, [], 200);

    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $employee = User::where('uuid', $uuid)->first();
        if(!$employee) {
            return $this->sendRes(translate('employee not found'), false, [], [], 400);
        }
        return $this->form($request, $employee);

    }


    public function show(Request $request, $uuid) {
        $client = User::where('uuid', $uuid)->first();
        if(!$client) {
            return $this->sendRes(translate('client not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('client found'), true, $client);
    }


    public function delete(Request $request, $uuid) {
        $employee = User::where('uuid', $uuid)->first();
        if(!$employee) {
            return $this->sendRes(translate('employee not found'), false, [], [], 400);
        }
        $employee->delete();
        return $this->sendRes(translate('employee deleted successfully'), true, [], [], 200);
    }




}
