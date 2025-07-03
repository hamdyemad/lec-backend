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

class CaseController extends Controller
{
    use Res, FileUploads;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $cases = CaseModel::with('lawyer', 'client', 'invoice.service.case_type', 'case_status', 'city')
        ->where('lawyer_id', auth()->id())
        ->latest();
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

    public function show(Request $request, $uuid) {
        $case = CaseModel::with('lawyer', 'client', 'invoice.service.attachments', 'invoice.service.case_type',
        'case_status', 'city')
        ->where('lawyer_id', auth()->id())
        ->where('uuid', $uuid)->first();
        if(!$case) {
            return $this->sendRes(translate('case not found'), false, [], [], 400);
        }
        $case = new CaseResource($case);
        return $this->sendRes(translate('case found'), true, $case);
    }







}
