<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactUsResource;
use App\Models\ContactUs;
use App\Models\Country;
use App\Models\ShippingMethod;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    use Res;

    public function index(Request $request)
    {
        $contactUs = ContactUs::with('user')->latest();
        $per_page = $request->get('per_page', 12);
        $user_id = $request->get('user_id');
        $keyword = $request->get('keyword');

        if($user_id) {
            $contactUs = $contactUs->where('user_id', $user_id);
        }
        if($keyword) {
            $contactUs = $contactUs
            ->where('title', 'like', '%'. $keyword . '%')
            ->orWhere('message', 'like', '%'. $keyword . '%');
        }
        $contactUs = $contactUs->paginate($per_page);
        $contactUs->getCollection()->transform(function($cont) {
            return new ContactUsResource($cont);
        });
        return $this->sendRes('all contact us', true, $contactUs);
    }




    public function form(Request $request, $contactUs = null)
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'title' => $request->title,
            'message' => $request->message,
            'user_id' => auth()->id()
        ];

        if($contactUs) {
            $contactUs->update($data);
            $message = translate('message updated success');
        } else {
            $data['uuid'] = \Str::uuid();
            $contactUs = ContactUs::create($data);
            $message = translate('message sent success');
        }
        return $this->sendRes($message, true, [], [], 200);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $contactUs = ContactUs::where('uuid', $uuid)->first();
        if(!$contactUs) {
            return $this->sendRes(translate('contact us not found'), false, [], [], 400);
        }
        return $this->form($request, $contactUs);
    }

    public function show(Request $request, $uuid) {
        $contactUs = ContactUs::with('user')->where('uuid', $uuid)->first();
        if(!$contactUs) {
            return $this->sendRes(translate('contact us not found'), false, [], [], 400);
        }
        $contactUs = new ContactUsResource($contactUs);

        return $this->sendRes(translate('contact us found'), true, $contactUs);
    }

    public function delete(Request $request, $uuid) {
        $contactUs = ContactUs::where('uuid', $uuid)->first();
        if(!$contactUs) {
            return $this->sendRes(translate('contact us not found'), false, [], [], 400);
        }
        $contactUs->delete();
        return $this->sendRes(translate('contact us deleted successfully'), true, [], [], 200);
    }



}
