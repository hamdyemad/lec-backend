<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderMessage;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Payment;
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

    public $moyasarService;
    public $pushNotificaion;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(MoyasarService $moyasarService, PushNotificaion $pushNotificaion)
    {
        $this->moyasarService = $moyasarService;
        $this->pushNotificaion = $pushNotificaion;
    }


    public function call_back_payment(Request $request) {
        return $this->k_payment($request->id);
    }

    public function k_payment($payment_id) {
        $cost_paid_status = 10;
        $moysasar_payment = $this->moyasarService->payment($payment_id);
        if($moysasar_payment) {
            if($moysasar_payment['status'] == 'paid') {
                $payment = Payment::where('payment_gateway_id', $payment_id)->first();
                if($payment) {
                    $order_reference = $payment->order->reference;

                    if($payment->status != 'paid' && $payment->order->pay_status_id != $cost_paid_status) {
                        if($payment->order) {

                            $payment->order->status_id = $cost_paid_status;
                            $payment->order->pay_status_id = $cost_paid_status;
                            $payment->order->save();
                            if($payment->order->invoice) {
                                $payment->order->invoice->status_id = $cost_paid_status;
                                $payment->order->invoice->save();
                            }

                            $body = __('payments.The amount has been paid', [
                                'attribute' => $payment->amount . $payment->currency
                            ]);

                            $payment_method = $payment->source_type;
                            $content = __('payments.paid message', [
                                'amount' => $payment->amount,
                                'currency' => __('payments.SAR'),
                                'payment_method' => $payment_method,
                            ]);

                            $created_array = [
                                'sender_id' => $payment->order->client_id,
                                'receiver_id' => $payment->order->driver_id,
                                'order_id' => $payment->order->id,
                                'status_id' => $payment->order->status_id,
                                'content' => $content,
                                'message_type' => 'payment',
                                'project_message' => true,
                            ];
                            $message = Message::create($created_array);
                            // RealTime Chat
                            broadcast(new OrderMessage($message, $payment->order->id));
                            $payment->status = 'paid';
                            $payment->save();

                            $driver_id = $payment->order->driver_id;

                            $icon = '';
                            $res = $this->pushNotificaion
                            ->sendPushNotificationByUsers(["$driver_id"], "عملية دفع من الطلب رقم #$order_reference", $body, $icon, ['order_id' => $payment->order_id, 'order_type' => $payment->order->type]);
                            $header = $payment->order->reference;
                            $message = __('payments.paid');

                            Log::info('check event paid', [$driver_id]);


                            return view('admin.common.moyasar.paid', compact('header', 'message'));
                        }
                    } else {
                        if($payment->status == 'paid') {
                            $header = "عملية الدفع رقم: $order_reference";
                            $message = __('payments.payment paid before');
                            return view('admin.common.moyasar.paid', compact('header', 'message'));
                        } else {
                            $header = 'عملية الدفع';
                            $message = __('payments.not found');
                            return view('admin.common.moyasar.faild', compact('header', 'message'));
                        }
                    }
                } else {
                    $header = 'عملية الدفع';
                    $message = __('payments.not found');
                    return view('admin.common.moyasar.faild', compact('header', 'message'));
                }

            } else {
                $header = 'خطأ';
                $message = __('payments.not found');
                return view('admin.common.moyasar.faild', compact('message', 'header'));
            }
        }
    }
    public function k_payment_faild($payment_id) {
        $moysasar_payment = $this->moyasarService->payment($payment_id);
        if($moysasar_payment) {

            if($moysasar_payment['status'] == 'failed') {
                $payment = Payment::where('payment_gateway_id', $payment_id)->first();
                $payment->status = 'failed';
                $payment->save();
                $header = 'خطأ';
                $message = __('payments.not found');
                return view('admin.common.moyasar.faild', compact('message', 'header'));
            } else {
                $header = 'خطأ';
                $message = __('payments.not found');
                return view('admin.common.moyasar.faild', compact('message', 'header'));
            }
        }
    }


    public function confirm_stc_payment(Request $request) {
        $rules = [
            'url' => ['required', 'url', "regex:/^https:\/\/api\.moyasar\.com\/v1\/stc_pays\//"],
            'otp_value' => ['required', 'string'],
        ];



        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }


        $response = $this->moyasarService->payment_stc_otp($request->url, $request->otp_value);
        if(isset($response['type'])) {
            $errors = [];
            $other_errors = [];
            // (isset($response['errors']['source.number'])) ? $errors['card_number'] = $response['errors']['source.number'] : '';
            // (isset($response['errors']['source.cvc'])) ? $errors['card_cvc'] = $response['errors']['source.cvc'] : '';
            // (isset($response['errors']['source.month'])) ? $errors['card_month'] = $response['errors']['source.month'] : '';
            // (isset($response['errors']['source.year'])) ? $errors['card_year'] = $response['errors']['source.year'] : '';
            // (isset($response['errors']['year'])) ? $errors['card_year'] = $response['errors']['year'] : '';
             // وضع أي أخطاء أخرى في مصفوفة "otherErrors"
            // foreach ($response['errors'] as $key => $error) {
            //     if (!in_array($key, ['source.number', 'source.cvc', 'source.month', 'source.year', 'year'])) {
            //         $other_errors[$key] = $error;
            //     }
            // }
            $errors = array_merge($errors, $other_errors);

            return $this->sendRes($response['message'], false, [], $errors, 400);
        }


        if(isset($response['status'])) {
            $payment_gateway_id = $response['id'];
            $payment = Payment::with('order')->where('payment_gateway_id', $payment_gateway_id)->firsT();
            if($payment) {
                $data = [
                    'status' => $response['status'],
                    'reference' => $payment->order->reference,
                ];
                if($response['status'] == 'paid') {
                    return $this->sendRes(__('payments.paid'), true, $data);
                } else {
                    return $this->sendRes(__('payments.failed'), false, $data);
                }
            } else {
                return $this->sendRes(__('payments.not found'), false, [], [], 400);
            }

        }


    }





}
