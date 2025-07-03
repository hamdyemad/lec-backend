<?php

namespace App\Http\Controllers\Api\Lawyer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseLawyerOrderResource;
use App\Http\Resources\CaseResource;
use App\Http\Resources\CaseStatusResource;
use App\Http\Resources\UserResource;
use App\Models\ApiKey;
use App\Models\CaseLawyerOrder;
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

class CaseOrderController extends Controller
{
    use Res, FileUploads;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $auth = auth()->user();
        $cases_orders = CaseLawyerOrder::with('case.invoice.service.client.city', 'case.invoice.service.case_type', 'lawyer')->where('lawyer_id', $auth->id)->latest();
        $per_page = $request->get('per_page', 12);
        $city_id = $request->get('city_id', '');
        $status = $request->get('status', '');

        if($city_id) {
            $cases_orders = $cases_orders->whereHas('case.invoice.service.client.city', function($city) use($city_id) {
                $city->where('id', $city_id);
            });
        }

        if($status) {
            $cases_orders = $cases_orders->where('status', $status);
        }


        $cases_orders = $cases_orders->paginate($per_page);
        $cases_orders->getCollection()->transform(function($cases_order) {
            return new CaseLawyerOrderResource($cases_order);
        });

        return $this->sendRes('all cases orders', true, $cases_orders);
    }



    public function show(Request $request, $uuid) {
        $auth = auth()->user();
        $case_order = CaseLawyerOrder::with('case.invoice.service.client.city', 'case.invoice.service.case_type', 'case.invoice.service.attachments', 'lawyer')
        ->where('lawyer_id', $auth->id)->where('uuid', $uuid)->first();
        if(!$case_order) {
            return $this->sendRes(translate('case order not found'), false, [], [], 400);
        }
        $case_order = new CaseLawyerOrderResource($case_order);
        return $this->sendRes(translate('case order found'), true, $case_order);
    }


    public function action(Request $request) {

        $rules = [
            'uuid' => ['required', 'exists:case_lawyer_orders,uuid'],
            'action_type' => ['required', 'in:accept,reject'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }
        $case_order = CaseLawyerOrder::where('lawyer_id', auth()->id())->where('uuid', $request->uuid)->first();
        if(!$case_order) {
            return $this->sendRes(translate('case order not found'), false, [], [], 400);
        }
        if($case_order->status != 'default') {
            return $this->sendRes(translate('case order updated before'), false, [], [], 400);
        }

        $case_order->status = $request->action_type;
        $case_order->save();

        if($case_order->status == 'accept') {
            $case_order->case->lawyer_id = $case_order->lawyer_id;
            $case_order->case->save();
        }


        return $this->sendRes(translate('case order updated successfully'), true);
    }




}
