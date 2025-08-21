<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderMessage;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserType;
use App\Service\MoyasarService;
use App\Service\PushNotificaion;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    use Res, FileUploads;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
    }


    public function payment_methods(Request $request)
    {
        $payment_methods = PaymentMethod::all();
        if ($payment_methods) {
            return $this->sendRes(translate('payment methods data'), true, $payment_methods, [], 200);
        } else {
            return $this->sendRes(translate('no payment methods found'), false, [], [], 404);
        }
    }


    public function stripe_webhook(Request $request) {

        $payment_gateway_id = $request['data']['object']['id'];
        $type = $request['type'];
        $payment = Payment::where('payment_gateway_id', $payment_gateway_id)->first();
        if($payment) {
            $payment->update([
                'status' => explode('.', $type)[1]
            ]);
        }
        Log::info('hello dear', $request['data']);
        Log::info('type' . $type);


    }





}
