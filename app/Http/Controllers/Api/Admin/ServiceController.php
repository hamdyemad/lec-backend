<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Status;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    use Res, FileUploads;


    public function index(Request $request)
    {
        $services = Service::with('case_type','status', 'client.city', 'client.country')->latest();

        $per_page = $request->get('per_page', 12);
        $keyword = $request->get('keyword', '');
        $status_id = $request->get('status_id', '');
        $city_id = $request->get('city_id', '');

        if($keyword) {
            $services = $services
            ->where('reference', 'like', "%$keyword%")
            ->orWhere('date', 'like', "%$keyword%")
            ->orWhere('describe_case', 'like', "%$keyword%")
            ->orWhere('facts_in_details', 'like', "%$keyword%")
            ->orWhere('subject_of_the_invitation', 'like', "%$keyword%");
        }

        if($city_id) {
            $services = $services->whereHas('client.city', function($city) use($city_id) {
                $city->where('id', $city_id);
            });
        }

        ($status_id) ? $services = $services->where('status_id', $status_id) : null;

        $services = $services->paginate($per_page);


        $services->getCollection()->transform(function($service) {
            return new ServiceResource($service);
        });

        return $this->sendRes('all services', true, $services);
    }




    public function form(Request $request, $service = null)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'national_id' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:255'],
            'mobile_code' => ['required', 'string', 'exists:countries,call_key'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'describe_case' => ['required', 'string'],
            'country_id' => ['required', 'string', 'exists:countries,id'],
            'city' => ['required', 'string'],
            'area' => ['required', 'string'],
            'facts_in_details' => ['required', 'string'],
            'subject_of_the_invitation' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'max:5210'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }

        if (!$service) {
            $data = [
                'uuid' => \Str::uuid(),
                'name' => $request->name,
                'national_id' => $request->national_id,
                'mobile' => $request->mobile,
                'mobile_code' => $request->mobile_code,
                'date' => $request->date,
                'describe_case' => $request->describe_case,
                'country_id' => $request->country_id,
                'city' => $request->city,
                'area' => $request->area,
                'facts_in_details' => $request->facts_in_details,
                'subject_of_the_invitation' => $request->subject_of_the_invitation,
                'user_id' => auth()->id(),
            ];
        }

        $service = Service::create($data);


        if($request->attachments) {
            foreach($request->attachments as $attachment) {
                $image = $this->uploadFiles($attachment, $this->service_attachment_path($service->id));
                $service->attachments()->create([
                    'path' => $image,
                ]);
            }
        }

        $message = translate('service made success');
        return $this->sendRes($message, true, [], [], 200);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }


    public function show(Request $request, $uuid) {
        $service = Service::with('case_type','status', 'client.city', 'client.country', 'attachments')->where('uuid', $uuid)->first();
        if(!$service) {
            return $this->sendRes(translate('service not found'), false, [], [], 400);
        }

        return $this->sendRes(translate('service found'), true, new ServiceResource($service));
    }


    public function action(Request $request, $uuid = null)
    {

        $service = Service::where('uuid', $uuid)->first();
        if(!$service) {
            return $this->sendRes(translate('service not found'), false, [], [], 400);
        }
        if($service->status && $service->status->type != 'default') {
            return $this->sendRes(translate('already made an action before for this service'), false, [], [], 400);
        }

        $rules = [
            'action_type' => ['required', 'in:accept,reject'],
            'price' => ['required_if:action_type,accept'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        if($request->action_type == 'reject') {
            $rejected_status = Status::where(['type' => 'reject'])->first();
            if(!$rejected_status) {
                return $this->sendRes(translate('rejected status not found'), false, [], [], 400);
            }
            $service->update([
                'status_id' => $rejected_status->id
            ]);
            $message = translate('service rejected');
        } else {
            $approved_status = Status::where(['type' => 'accept'])->first();
            if(!$approved_status) {
                return $this->sendRes(translate('approved status not found'), false, [], [], 400);
            }
            $service->update([
                'status_id' => $approved_status->id,
                'price' => $request->price,
            ]);
            $message = translate('service accepted');
        }

        return $this->sendRes($message, true, [], [], 200);
    }



}
