<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use App\Models\Country;
use App\Models\ShippingMethod;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    use Res;


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


        $data['uuid'] = \Str::uuid();
        $ContactUs = ContactUs::create($data);
        $message = translate('message sent success');
        return $this->sendRes($message, true, $ContactUs, [], 200);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }
}
