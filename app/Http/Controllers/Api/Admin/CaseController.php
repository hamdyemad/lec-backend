<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseLawyerOrderResource;
use App\Http\Resources\CaseResource;
use App\Http\Resources\CaseStatusResource;
use App\Http\Resources\UserResource;
use App\Models\ApiKey;
use App\Models\CaseModel;
use App\Models\CaseStatus;
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

class CaseController extends Controller
{
    use Res, FileUploads;

    public $lawyer_type_id = 2;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cases = CaseModel::with('lawyer', 'client', 'invoice.service.case_type', 'case_status', 'city')->latest();
        $per_page = $request->get('per_page', 12);

        $keyword = $request->get('keyword', '');
        $city_id = $request->get('city_id', '');
        $status_id = $request->get('status_id', '');

        if($keyword) {
            $cases = $cases
            ->where('reference', 'like', "%$keyword%")
            ->orWhere('case_name', 'like', "%$keyword%")
            ->orWhere('start_date', 'like', "%$keyword%");
        }

        if($city_id) {
            $cases = $cases->whereHas('city', function($city) use($city_id) {
                $city->where('id', $city_id);
            });
        }

        if($status_id) {
            $cases = $cases->whereHas('case_status', function($city) use($status_id) {
                $city->where('id', $status_id);
            });
        }


        $cases = $cases->paginate($per_page);
        $cases->getCollection()->transform(function($case) {
            return new CaseResource($case);
        });

        return $this->sendRes('all cases', true, $cases);
    }


    public function statuses(Request $request) {
        $statuses = CaseStatus::latest()->get();
        $statuses = CaseStatusResource::collection($statuses);
        return $this->sendRes('all case statuses', true, $statuses);

    }


    public function assign_lawyer(Request $request) {
        $rules = [
            'uuid' => ['required', 'string', 'max:255', 'exists:cases,uuid'],
            'lawyer_id' => ['required', Rule::exists('users', 'id')->where('user_type_id', $this->lawyer_type_id)],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $case = CaseModel::where('uuid', $request->uuid)->first();

        if($case->lawyer_id != null) {
            return $this->sendRes('case assigned by another lawyer', false, [], [], 400);
        }

        $reference = 1;
        $last_case_order = $case->case_orders()->where('lawyer_id', $request->lawyer_id)->latest()->first();
        if($last_case_order) {
            $reference = $last_case_order->reference + 1;
        }

        $case_order = $case->case_orders()->create([
            'uuid' => \Str::uuid(),
            'lawyer_id' => $request->lawyer_id,
            'reference' => $reference,
            'status' => 'default'
        ]);

        return $this->sendRes('case order sent to the the lawyer success', true, new CaseLawyerOrderResource($case_order));

    }

    public function change_case_status(Request $request) {
        $rules = [
            'uuid' => ['required', 'string', 'max:255', 'exists:cases,uuid'],
            'status_id' => ['required', Rule::exists('cases_statuses', 'id')],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $case = CaseModel::where('uuid', $request->uuid)->first();


        $case->update([
            'case_status_id' => $request->status_id
        ]);

        return $this->sendRes('case status updated', true);

    }

    // public function form(Request $request, $client = null) {
    //     $rules = [
    //         'name' => ['required', 'string', 'max:255'],
    //         'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($client?->id), 'string', 'max:255'],
    //         'phone_code' => ['required', 'string', 'exists:countries,call_key'],
    //         'phone' => ['required', 'string'],
    //         'country_id' => ['required', 'exists:countries,id'],
    //         'city_id' => ['nullable', 'string', 'exists:cities,id'],
    //         'national_id' => ['nullable', 'string', 'max:255'],
    //     ];

    //     if(!$client) {
    //         $rules['password'] = ['required', 'string','min:8', 'confirmed'];
    //     }

    //     $validator = Validator::make($request->all(), $rules);

    //     if($validator->fails()) {
    //         return $this->errorResponse($validator);
    //     }

    //     $data = [
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'phone' => $request->phone,
    //         'phone_code' => $request->phone_code,
    //         'country_id' => $request->country_id,
    //         'city_id' => $request->city_id,
    //         'national_id' => $request->national_id,
    //     ];
    //     if($client) {
    //         $client->update($data);
    //         $message = translate('client has been updated successfully !');
    //     } else {
    //         $data['password'] = Hash::make($request->password);
    //         $data['uuid'] = \Str::uuid();
    //         $data['user_type_id'] = $this->user_type_id;
    //         $data['active'] = true;
    //         $client = User::create($data);
    //         $message = translate('client has been created successfully !');
    //     }
    //     return $this->sendRes($message, true, [], [], 200);

    // }

    // public function store(Request $request)
    // {
    //     return $this->form($request);
    // }

    // public function edit(Request $request, $uuid)
    // {
    //     $client = User::where('uuid', $uuid)->first();
    //     if(!$client) {
    //         return $this->sendRes(translate('client not found'), false, [], [], 400);
    //     }
    //     return $this->form($request, $client);

    // }


    public function show(Request $request, $uuid) {
        $case = CaseModel::with('lawyer', 'client', 'invoice.service.attachments', 'invoice.service.case_type', 'case_status', 'city')->where('uuid', $uuid)->first();
        if(!$case) {
            return $this->sendRes(translate('case not found'), false, [], [], 400);
        }
        $case = new CaseResource($case);
        return $this->sendRes(translate('case found'), true, $case);
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
