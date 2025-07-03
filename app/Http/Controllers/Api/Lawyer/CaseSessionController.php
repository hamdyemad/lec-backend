<?php

namespace App\Http\Controllers\Api\Lawyer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseLawyerOrderResource;
use App\Http\Resources\CaseResource;
use App\Http\Resources\CaseSessionResource;
use App\Http\Resources\CaseSessionStatusResource;
use App\Http\Resources\CaseStatusResource;
use App\Http\Resources\UserResource;
use App\Models\ApiKey;
use App\Models\CaseLawyerOrder;
use App\Models\CaseModel;
use App\Models\CaseSession;
use App\Models\CaseSessionStatus;
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

class CaseSessionController extends Controller
{
    use Res, FileUploads;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $cases_sessions = CaseSession::with('status','case.lawyer', 'case.client', 'case.invoice.service.case_type', 'case.case_status', 'case.city')
        ->where('lawyer_id', auth()->id())
        ->latest();
        $per_page = $request->get('per_page', 12);

        $status_id = $request->get('status_id', '');

        if($status_id) {
            $cases_sessions = $cases_sessions->whereHas('status', function($city) use($status_id) {
                $city->where('id', $status_id);
            });
        }


        $cases_sessions = $cases_sessions->paginate($per_page);

        $cases_sessions->getCollection()->transform(function($case) {
            return new CaseSessionResource($case);
        });

        return $this->sendRes('all cases sessions', true, $cases_sessions);
    }



    public function statuses(Request $request) {
        $statuses = CaseSessionStatus::latest()->get();
        $statuses = CaseSessionStatusResource::collection($statuses);
        return $this->sendRes('all statuses', true, $statuses);

    }


    public function form(Request $request, $client = null) {
        $rules = [
            'case_uuid' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date', 'date_format:Y-m-d'],
            'session_hour' => ['required', 'date_format:H:i'],
            'duration' => ['required', 'integer', 'min:1'],
            'court_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];


        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $case = CaseModel::where('uuid', $request->case_uuid)->where('lawyer_id', auth()->id())->firstOrFail();

        if(!$case) {
            return $this->sendRes(translate('case not found'), false, [], [], 400);
        }

        $default_status = CaseSessionStatus::where('default_status', true)->first();

        $session = $case->sessions()->create([
            'uuid' => \Str::uuid(),
            'lawyer_id' => auth()->id(),
            'status_id' => $default_status ? $default_status->id : null,
            'session_date' => $request->session_date,
            'session_hour' => $request->session_hour,
            'duration' => $request->duration,
            'court_name' => $request->court_name,
            'notes' => $request->notes,
        ]);

        $message = translate('session has been created successfully !');
        return $this->sendRes($message, true, [], [], 200);

    }

    public function store(Request $request)
    {
        return $this->form($request);
    }



    public function show(Request $request, $uuid) {
        $case_session = CaseSession::with('histories.status','status','case.lawyer', 'case.client', 'case.invoice.service.case_type', 'case.case_status', 'case.city')
        ->where('lawyer_id', auth()->id())
        ->where('uuid', $uuid)
        ->first();
        if(!$case_session) {
            return $this->sendRes(translate('case session not found'), false, [], [], 400);
        }
        $case_session = new CaseSessionResource($case_session);
        return $this->sendRes(translate('case session found'), true, $case_session);
    }

    public function action(Request $request) {

        $rules = [
            'uuid' => ['required', 'exists:cases_sessions,uuid'],
            'status_id' => ['required', 'exists:cases_session_statuses,id'],
            'notes' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }
        $case_session = CaseSession::where('lawyer_id', auth()->id())->where('uuid', $request->uuid)->first();
        if(!$case_session) {
            return $this->sendRes(translate('case session not found'), false, [], [], 400);
        }

        $status = CaseSessionStatus::find($request->status_id);
        $case_session->status_id = $status->id;
        $case_session->save();
        $case_session->histories()->create([
            'case_session_id' => $case_session->id,
            'status_id' => $status->id,
            'notes' => $request->notes ?? null,
        ]);

        return $this->sendRes(translate('case session updated successfully'), true);
    }


}
