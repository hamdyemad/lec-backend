<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderMessage;
use App\Events\OrderStatusChangeEvent;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Iban;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Order;
use App\Models\Status;
use App\Models\User;
use App\Models\UserType;
use App\Service\PushNotificaion;
use App\Traits\Chat;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WithdrawlsController extends Controller
{
    use Res, FileUploads, Chat;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $pushNotificaion;

    public function __construct(PushNotificaion $pushNotificaion)
    {
        $this->pushNotificaion = $pushNotificaion;
    }




     public function store(Request $request)
     {

        $user = auth()->user();

        if($user->iban) {
            $rules = [
                'amount' => ['required', 'numeric']
            ];
            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                $message = implode('<br>', $validator->errors()->all());
                return $this->sendRes($message, false, [], $validator->errors(), 400);
            }

            if($request->amount <= $user->wallet) {
                $user->withdrawls()->create([
                    'uuid' => \Str::uuid(),
                    'iban_id' => $user->iban->id,
                    'amount' => $request->amount,
                    'payment_method_id' => '2'
                ]);
                return $this->sendRes(__('validation.success'), true);
            } else {
                return $this->sendRes(__("main.you don't have balance"), true);
            }

        } else {
            return $this->sendRes(__('ibans.iban not found'), false, [], [], 400);
        }

    }




}
