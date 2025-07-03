<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ServiceResource;
use App\Models\CaseModel;
use App\Models\CaseStatus;
use App\Models\Invoice;
use App\Models\Status;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoicesController extends Controller
{
    use Res, FileUploads;


    public function index(Request $request)
    {
        $invoices = Invoice::with('service.client', 'account')->latest();
        $per_page = $request->get('per_page', 12);
        $keyword = $request->get('keyword', '');
        $date = $request->get('date', '');
        $status = $request->get('status', '');
        $client_id = $request->get('client_id', '');

        if($keyword) {
            $invoices = $invoices->whereHas('service.client', function($client) use($keyword) {
                $client
                ->where('name', 'like', "%$keyword%")
                ->orWhere('phone', 'like', "%$keyword%");
            });
        }
        if($client_id) {
            $invoices = $invoices->whereHas('service.client', function($client) use($client_id) {
                $client->where('id', $client_id);
            });
        }


        if($status) {
            $invoices = $invoices->where('paid_status', '=', $status);
        }

        if($date) {
            $invoices = $invoices->where('created_at', 'like', "%$date%");
        }


        $invoices = $invoices->paginate($per_page);


        $invoices->getCollection()->transform(function($invoice) {
            return new InvoiceResource($invoice);
        });

        return $this->sendRes('all invoices', true, $invoices);
    }





    public function show(Request $request, $uuid) {
        $invoice = Invoice::with('service.client', 'attachments','account')->where('uuid', $uuid)->first();
        if(!$invoice) {
            return $this->sendRes(translate('invoice not found'), false, [], [], 400);
        }

        return $this->sendRes(translate('invoice found'), true, new InvoiceResource($invoice));
    }


     public function pay(Request $request) {
        $rules = [
            'uuid' => ['required', 'exists:invoices,uuid']
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $defaultCaseStatus = CaseStatus::where('type', 'default')->first();

        $invoice = Invoice::where('uuid', $request->uuid)->first();
        if(!$invoice) {
            return $this->sendRes(translate('invoice not found'), false, [], [], 400);
        }

        // if($invoice->paid_status == 'paid') {
        //     return $this->sendRes(translate('invoice paid before'), false, [], [], 400);
        // }

        $invoice->update([
            'paid_status' => 'paid'
        ]);


        $last = CaseModel::orderBy('reference', 'desc')->first();
        if($last) {
            $reference = $last->reference + 1;
        } else {
            $reference = 1;
        }

        $case = CaseModel::create([
            'uuid' => \Str::uuid(),
            'reference' => $reference,
            'client_id' => $invoice->service->user_id,
            'invoice_id' => $invoice->id,
            'city_id' => $invoice->service->client->city_id,
            'case_status_id' => $defaultCaseStatus->id,
            'start_date' => $invoice->created_at
        ]);

        $invoice->service->case_id = $case->id;
        $invoice->service->save();
        return $this->sendRes(translate('invoice paid success and the case created'), true);
    }



}
