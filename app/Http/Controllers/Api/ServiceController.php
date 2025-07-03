<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\CaseTypeResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ServiceResource;
use App\Http\Resources\StatusResource;
use App\Models\Currency;
use App\Models\Invoice;
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
        $services = Service::with('case_type','status', 'case', 'client.city', 'client.country')->latest();

        $per_page = $request->get('per_page', 12);

        $baseCurrency = Currency::where('base_currency', 1)->first();


        $services = $services->paginate($per_page);

        $services->getCollection()->transform(function ($service) use($baseCurrency) {
            return [
                'id' => $service->id,
                'uuid' => $service->uuid,
                'reference' => $service->reference,
                'case_type' => $service->case_type ? new CaseTypeResource($service->case_type) : null,
                'date' => $service->date,
                'price' => $service->price,
                'currency' => $baseCurrency->symbol,
                'status' => $service->status ? new StatusResource($service->status) : null,
            ];
        });
        return $services;

        return $this->sendRes('all services', true, $services);
    }

    public function form(Request $request, $service = null)
    {
        $rules = [
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'describe_case' => ['required', 'string'],
            'facts_in_details' => ['required', 'string'],
            'subject_of_the_invitation' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'max:5210'],
            'case_type_id' => ['required', 'exists:cases_types,id'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $default_status = Status::where('type', 'default')->first();
        if(!$default_status) {
            return $this->sendRes(translate('default status not found'), false, [], [], 400);
        }

        $last = Service::orderBy('reference', 'desc')->first();
        if($last) {
            $reference = $last->reference + 1;
        } else {
            $reference = 1;
        }

        if (!$service) {
            $data = [
                'uuid' => \Str::uuid(),
                'status_id' => $default_status->id,
                'case_type_id' => $request->case_type_id,
                'reference' => $reference,
                'date' => $request->date,
                'describe_case' => $request->describe_case,
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

        $message = translate('service created successfully');
        return $this->sendRes($message, true, [], [], 200);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }


    public function show(Request $request, $uuid) {
        $service = Service::with('case_type', 'case','status', 'client.city', 'client.country', 'attachments')->where('uuid', $uuid)->first();
        if(!$service) {
            return $this->sendRes(translate('service not found'), false, [], [], 400);
        }

        return $this->sendRes(translate('service found'), true, new ServiceResource($service));
    }


    public function action(Request $request) {

        $rules = [
            'uuid' => ['required', 'exists:services,uuid'],
            'action_type' => ['required', 'in:accept,reject'],
            'account_id' => ['required_if:action_type,accept', 'exists:accounts,id'],
            'invoice_attachments' => ['nullable', 'array'],
            'invoice_attachments.*' => ['nullable', 'file', 'max:5120'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }
        $service = Service::with('status', 'client.city', 'client.country', 'attachments')->where('uuid', $request->uuid)->first();
        if(!$service) {
            return $this->sendRes(translate('service not found'), false, [], [], 400);
        }

        if($service->status->type != 'accept') {
            return $this->sendRes(translate('service status is not accepted by administration'), false, [], [], 400);
        }

        $invoice = $service->invoices()->create([
            'uuid' => \Str::uuid(),
            'account_id' => $request->account_id,
        ]);
        if($request->invoice_attachments) {
            foreach($request->invoice_attachments as $invoice_attachment) {
                $attachment = $this->uploadFiles($invoice_attachment, $this->service_attachment_path($service->id));
                $invoice->attachments()->create([
                    'path' => $attachment,
                ]);
            }
        }
        $invoice->load('service', 'attachments');

        return $this->sendRes(translate('invoice created success'), true, new InvoiceResource($invoice));
    }


}
