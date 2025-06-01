<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreationEvent;
use App\Events\OrderFindEvent;
use App\Events\SendMessage;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Message;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Status;
use App\Models\User;
use App\Models\UserType;
use App\Service\LogistiService;
use App\Service\MesagatService;
use App\Service\MoyasarService;
use App\Service\PushNotificaion;
use App\Traits\Delivery;
use App\Traits\FileUploads;
use App\Traits\Location;
use App\Traits\Res;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use Res, FileUploads, Location, Delivery;

    public $moyasarService;
    public $pushNotificaion;
    public $logistiService;
    public $mesagatService;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function __construct(MoyasarService $moyasarService, PushNotificaion $pushNotificaion, LogistiService $logistiService, MesagatService $mesagatService)
    {
        $this->mesagatService = $mesagatService;
        $this->moyasarService = $moyasarService;
        $this->pushNotificaion = $pushNotificaion;
        $this->logistiService = $logistiService;

    }
    public function index(Request $request)
    {
        $rules = [
            'status_type' => ['nullable', 'in:processing,finished'],
            'paginate' => ['nullable', 'integer']
        ];

        $messages  = [
            'status_type.in' => '(processing,finished) نوع الحالة يجب ان يكون بين '
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $user = auth()->user();
        $paginate = $request->paginate;

        $lang = app()->getLocale();
        $status = "id,name_$lang as name,color,bg,border";
        $orders = $user->orders()->with("status:$status", "pay_status:$status")->latest();
        ($request->client_lat) ? $orders = $orders->where('client_lat', 'like', "%$request->client_lat%") : '';
        ($request->client_lng) ? $orders = $orders->where('client_lng', 'like', "%$request->client_lng%") : '';
        ($request->place_lng) ? $orders = $orders->where('place_lng', 'like', "%$request->place_lng%") : '';
        ($request->place_lat) ? $orders = $orders->where('place_lat', 'like', "%$request->place_lat%") : '';
        ($request->driver_id) ? $orders = $orders->where('driver_id', 'like', "%$request->driver_id%") : '';
        ($request->driver_lat) ? $orders = $orders->where('driver_lat', 'like', "%$request->driver_lat%") : '';
        ($request->driver_lng) ? $orders = $orders->where('driver_lng', 'like', "%$request->driver_lng%") : '';

        if($request->status_type) {
            $ids = Status::where('type', $request->status_type)->pluck('id');
            $orders = $orders->whereIn('status_id', $ids);
        }

        if($request->status_id) {
            $status_id = explode(',', $request->status_id);
            $orders = $orders->whereIn('status_id', $status_id);
        }


        if($paginate) {
            $orders = $orders->paginate($paginate);
        } else {
            $orders = $orders->get();
        }

        return $this->sendRes(__('orders.list'), true, $orders);
    }

    public function store(Request $request)
    {
        // Find Logisti Service From Web
        $logistiWebService = Service::where('name', 'logisti')->first();
        $reference = Order::generateReference();
        $creation_order_status = 1;
        $user = auth()->user();
        $rules = [
            'type' => ['required', 'in:delivery,purchase'],
            'client_location_id' => ['required_if:type,purchase', 'exists:users_locations,id', function($att, $val, $fail) use($user) {
                $location = $user->locations()->where('id', $val)->first();
                if(!$location) {
                    $fail(__('locations.not found'));
                }
            }],
            'client_lat' => ['required_if:type,delivery'],
            'client_lng' => ['required_if:type,delivery'],
            'place_lng' => ['required_if:type,delivery', 'array', 'max:2'],
            'place_lat' => ['required_if:type,delivery', 'array', 'max:2'],
            'content' => ['required_if:type,delivery', 'array', 'max:2'],
            'file' => ['required_with:file_type,file_seconds', 'file', 'max:20480'],
            'file_type' => ['required_with:file', 'in:record'],
            'file_seconds' => ['required_if:file_type,record', 'max:255']
        ];


        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request) {
            if(is_array($request['place_lng']) || is_array($request['place_lat'])) {
                $lngCount = count($request['place_lng'] ?? []);
                $latCount = count($request['place_lat'] ?? []);
                if ($lngCount !== $latCount) {
                    $validator->errors()->add('place_lng', 'الطول والعرض يجب ان يطابقوا بعض');
                    $validator->errors()->add('place_lat', 'الطول والعرض يجب ان يطابقوا بعض');
                }
            }
        });

        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $otp = rand(100000, 999999);
        $data = [
            'reference' => $reference,
            'client_id' => $user->id,
            'client_location_id' => $request->client_location_id,
            'status_id' => $creation_order_status, //Under review,
            'pay_status_id' => $creation_order_status,
            'otp' => $otp,
        ];





        if($request->type == 'purchase') {
            $data['type'] = 'purchase';
            // Purchase
            if(count($user->carts) > 0) {
                $sellers_ids =  $user->carts->pluck('seller_id')->unique();
                $client_location = $user->locations()->find($request->client_location_id);
                $total_price = 0;
                $total_count = 0;
                $total_distance = 0;

                if($client_location) {
                    $data['client_lat'] = $client_location->latitude;
                    $data['client_lng'] = $client_location->longitude;
                    $order = Order::create($data);
                    if($order) {
                        // First Point
                        $nearestSeller = $this->getNearestSellerLocation($client_location, $sellers_ids);
                        if($nearestSeller) {
                            $total_distance += $nearestSeller['distance'];
                            $order_pickup = $order->pickups()->create([
                                'seller_id' => $nearestSeller['location']['user_id'],
                                'seller_location_id' => $nearestSeller['location']['id'],
                                'distance' => $nearestSeller['distance'],
                                'status_id' => $creation_order_status,
                            ]);
                            $carts = $user->carts()->where('seller_id', $order_pickup->seller_id)->get();
                            foreach($carts as $cart) {
                                $price = ($cart->product->price - $cart->product->discount);
                                $order->items()->create([
                                    'order_pickup_id' => $order_pickup->id,
                                    'product_id' => $cart->product_id,
                                    'count' => $cart->count,
                                    'price' => $price,
                                    'total' => $price *  $cart->count
                                ]);
                                $total_count += $cart->count;
                                $total_price += $price * $cart->count;
                            }
                        }

                        // Second Point
                        $nextNearestSellerToFirstSeller = $this->getNearestSellerLocation($client_location, $sellers_ids, $nearestSeller['location']);
                        if($nextNearestSellerToFirstSeller) {
                            $total_distance += $nearestSeller['distance'];
                            $order_pickup = $order->pickups()->create([
                                'seller_id' => $nextNearestSellerToFirstSeller['location']['user_id'],
                                'seller_location_id' => $nextNearestSellerToFirstSeller['location']['id'],
                                'distance' => $nextNearestSellerToFirstSeller['distance'],
                                'status_id' => $creation_order_status,
                            ]);
                            $carts = $user->carts()->where('seller_id', $order_pickup->seller_id)->get();
                            foreach($carts as $cart) {
                                $price = $cart->product->price - $cart->product->discount;
                                $order->items()->create([
                                    'order_pickup_id' => $order_pickup->id,
                                    'product_id' => $cart->product_id,
                                    'count' => $cart->count,
                                    'price' => $price,
                                    'total' => $price *  $cart->count
                                ]);
                                $total_count += $cart->count;
                                $total_price += $price * $cart->count;

                            }
                        }

                        // Get Total Delivery Cost
                        $delivery = $this->getFullDeliveryPriceFromLocations($client_location, $sellers_ids);
                        // Update Totals
                        $order->total_kilo_price = round($delivery['total_kilo_price'], 2);
                        $order->tax = $delivery['tax'];
                        $order->total_distance = round($total_distance, 2);
                        $order->total_delivery = round($delivery['total_kilo_price_after_tax'], 2);
                        $order->total_count = $total_count;
                        $order->total = $total_price;
                        $order->total_with_delivery = round($order->total + $delivery['total_kilo_price_after_tax'], 2);
                        $order->save();

                        // Remove Cart After Making Order
                        $user->carts()->delete();
                        $order = Order::with(['client', 'items', 'pickups'])->find($order->id);

                        try {
                            if($logistiWebService && $logistiWebService->status == true) {
                                // Create Order Logisti
                                $storetName = '';
                                $storeLocation = '';
                                foreach($order->pickups as $pickup) {
                                    $storetName .= $pickup->seller->username . ' ';
                                    $storeLocation .= $pickup->seller_location->latitude . ', ' . $pickup->seller_location->longitude;
                                }
                                $coordinates = $client_location->latitude . ', ' . $client_location->longitude;
                                $fullNumberOfClient = $order->client->mobile_code . $order->client->mobile;
                                $data = [
                                    'orderNumber' => $order->reference,
                                    'coordinates' => $coordinates,
                                    'storetName' => $storetName,
                                    'storeLocation' => $storeLocation,
                                    'recipientMobileNumber' => $fullNumberOfClient
                                ];

                                $returnedCreation = $this->createLogistiOrder($data, $order);
                                if($returnedCreation) {
                                    return $returnedCreation;
                                }
                            }
                        }
                        catch(Exception $e) {

                        }

                        foreach ($order->pickups as $pickup) {
                            $icon = $order->client->image ?? '';
                            $body = __('orders.client add new order please check it');
                            // Send Notification to The Sellers To Approve Or UnApprove The Order
                            $seller_id = $pickup->seller_id;
                            $notify_data = [
                                'pickup_id' => $pickup->id,
                                'order_type' => $order->type,
                                'sound' => 'notification_sound_order'
                            ];
                            $res = $this->pushNotificaion
                            ->sendPushNotificationByUsers(["$seller_id"], "Order:#$order->reference", $body, $icon, $notify_data);
                        }

                        return $this->sendRes(__('sellers.your order is waiting the seller action'), true, $order);
                    }
                }

            } else {
                return $this->sendRes(__('carts.not found'), false, [], [], 400);
            }
        } else {
            // Delivery
            $data['type'] = 'delivery';
            $data['client_lat'] = $request->client_lat;
            $data['client_lng'] = $request->client_lng;

            $order = Order::create($data);
            if($order) {
                $storeLocation = '';
                $locations = [];

                if(isset($request->place_lat)) {
                    $total_delivery = 0;
                    $total_distance = 0;
                    $total_kilo_price = 0;
                    for ($i=0; $i <= count($request->place_lat); $i++) {
                        if(isset($request->place_lat[$i])) {
                            $locations[] = [
                                'lat' => $request->place_lat[$i],
                                'lng' => $request->place_lng[$i],
                            ];

                            $distance_between_client_and_place = $this->haversine($request->client_lat, $request->client_lng, $request->place_lat[$i], $request->place_lng[$i]);
                            $delivery = $this->delivery_cost($distance_between_client_and_place);
                            $total_delivery += $delivery['total_kilo_price_after_tax'];
                            $total_distance += $distance_between_client_and_place;
                            $total_kilo_price += round($delivery['total_kilo_price'], 2);
                            $item = $order->items()->create([
                                'place_lng' => $request->place_lng[$i],
                                'place_lat' => $request->place_lat[$i],
                                'distance_between_client_and_place' => round($distance_between_client_and_place, 2),
                                'total_kilo_price' => round($delivery['total_kilo_price'], 2),
                                'tax' => round($delivery['tax'], 2),
                                'total' => round($delivery['total_kilo_price_after_tax'], 2),
                            ]);
                            $storeLocation = $request->place_lat[$i] . ', ' . $request->place_lng[$i] . ' ';

                            $order_id = $order->id;
                            $sender_id = auth()->user()->id;
                            $created_message = [
                                'sender_id' => $sender_id,
                                'order_id' => $order->id,
                                'order_item_id' => $item->id,
                                'status_id' => $order->status_id,
                                'content' => isset($request->content[$i]) ? $request->content[$i] : '',
                                'message_type' => 'order_creation',
                            ];
                            Message::create($created_message);


                        }
                    }
                    // update total delivery
                    $order->total_distance = round($total_distance, 2);
                    $order->total_kilo_price = round($total_kilo_price, 2);
                    $order->tax = round($delivery['tax'], 2);
                    $order->total_delivery = round($total_delivery, 2);
                    $order->save();
                }

                try {
                    // Create Order Logisti
                    if($logistiWebService && $logistiWebService->status == true) {
                        $storetName = 'delivery';
                        $coordinates = $request->client_lat . ', ' . $request->client_lng;
                        $fullNumberOfClient = $order->client->mobile_code . $order->client->mobile;
                        $data = [
                            'orderNumber' => $order->reference,
                            'coordinates' => $coordinates,
                            'storetName' => $storetName,
                            'storeLocation' => $storeLocation,
                            'recipientMobileNumber' => $fullNumberOfClient
                        ];

                        $returnedCreation = $this->createLogistiOrder($data, $order);
                        if($returnedCreation) {
                            return $returnedCreation;
                        }
                    }
                } catch(Exception $e) {

                }

            }



            if(isset($request->file)) {
                $created_message = [
                    'sender_id' => $sender_id,
                    'order_id' => $order->id,
                    'status_id' => $order->status_id,
                    'file_type' => isset($request->file_type) ? $request->file_type : null,
                    'file_seconds' => isset($request->file_seconds) ? $request->file_seconds : null,
                    'message_type' => 'order_creation',
                ];
                $file = $this->uploadFile($request, $this->messages_path . "order-$order_id/", 'file');
                $created_message['file'] = $file;

                Message::create($created_message);
            }



            $order = Order::with(['items.chat', 'chat' => function($query) {
                $query->where('file_type', 'record');
            }])->find($order->id);
            $order->cancel_button = true;


            $drivers = $this->near_by_drivers($locations);

            $drivers_ids =  array_map(function($driver) {
                return "$driver->user_id";
            }, $drivers);

            $title_notify = "طلب جديد";
            $body_notify = "يوجد طلب جديد حول موقعك برجاء الاطلاع عليه في استكشاف الطلبات";
            $icon = '';
            $notify_data = [
                'order_id' => $order->id,
                'order_type' => $order->type,
                'sound' => 'notification_sound_order'
            ];
            $res = $this->pushNotificaion
                 ->sendPushNotificationByUsers($drivers_ids, $title_notify, $body_notify, $icon, $notify_data);

            // foreach($drivers_ids as $driver_id) {
            //     broadcast(new OrderFindEvent(['driver_id' =>$driver_id, 'order' => $order]));
            // }

            return $this->sendRes(__('drivers.your order is waiting the driver action'), true, $order);
        }





    }



    public function near_by_drivers($locations = []) {
        $drivers = DB::table('tracking_locations')
        ->join('rc_users', 'tracking_locations.user_id', '=', 'rc_users.id')  // Assuming users table has the driver info
        ->select('tracking_locations.user_id', 'tracking_locations.latitude', 'tracking_locations.longitude')
        ->where('rc_users.type', '3')
        ->whereIn('tracking_locations.id', function($query) {
            $query->select(DB::raw('MAX(id)'))  // Get the latest entry (by MAX(id)) for each user_id
                  ->from('tracking_locations')
                  ->groupBy('user_id');  // Group by user_id to get the most recent location
        })
        // ->where('tracking_locations.created_at', '>=', now()->subMinutes(10))
        ->get();

        $nearbyDrivers = [];

        foreach ($drivers as $driver) {
            $distances = [];

            // Calculate distance to both locations
            foreach ($locations as $location) {
                $distance = $this->haversine($driver->latitude, $driver->longitude, $location['lat'], $location['lng']);
                $distances[] = $distance;
            }

            // Find the nearest location to the driver
            $nearestDistance = min($distances);
            $radius = settings('max_distance_drivers_find_orders');
            // If the nearest distance is within the radius, consider this driver as nearby
            if ($nearestDistance <= $radius) {
                $nearbyDrivers[] = $driver;
            }
        }

        return $nearbyDrivers;
    }



    public function createLogistiOrder($data, $order) {
        $responseCreateOrder = $this->logistiService->createOrder($data);
        if($responseCreateOrder['status'] == true) {
            $order->logisti_reference = $responseCreateOrder['data']['referenceCode'];
            $order->save();
            $responseAcceptOrder = $this->logistiService->acceptOrder($responseCreateOrder['data']['referenceCode']);
            if($responseAcceptOrder['status'] == false) {
                return $this->logistiService->logistiError($responseAcceptOrder);
            }
        } else {
            return $this->logistiService->logistiError($responseCreateOrder);
        }
    }





    public function show(Request $request, $order_id)
    {
        $client = auth()->user();

        $lang = app()->getLocale();
        $status = "id,name_$lang as name,color,bg,border";
        $order = $client->orders()->with('client_location', "status:$status", "pay_status:$status")->find($order_id);
        if($order->status_id == 1) {
            $order->cancel_button = true;
        } else {
            $order->cancel_button = false;
        }

        if($order->type == 'purchase') {
            $pickups = $order->pickups()->with(['seller_location' => function($query) {
                $query->withTrashed();
            }])->get();
            $pickups->load([
                'pickup_items.product' => function ($query) {
                    $query->withTrashed()->with('items');
                }
            ]);
            $pickups->load([
                'seller' => function ($query) {
                    $query->withTrashed()->select('id', 'uuid', 'username', 'image', 'mobile_code', 'mobile');
                }
            ]);

            $order->pickups = $pickups;
        } else {
           $order->load(['invoice', 'items.chat', 'chat' => function($query) {
                $query->where(['message_type' => 'order_creation', 'file_type' => 'record'])->first();
           }])->find($order_id);
        }

        $order->load([
            'driver' => function ($query) {
                $query->withTrashed()->select('id', 'uuid', 'username', 'image', 'mobile_code', 'mobile');
            }
        ]);

        if($order) {
            $data = [
                'order' => $order,
                'realtime' => [
                    'channel' => "order_$order_id",
                    'event_chat' => 'order_chat_message',
                    'event_tracking' => 'order_tracking_location',
                ],
            ];
            return $this->sendRes(__('orders.show'), true, $data);
        } else {
            return $this->sendRes(__('orders.not found'), false, [], [], 400);
        }

    }




    public function request_payment(Request $request, $order_id) {
        $user = auth()->user();
        $order = $user->orders()->with('invoice')->find($order_id);
        if($order) {
            $rules = [
                'type' => ['required', 'in:creditcard,stcpay'],
                'card_name' => ['required_if:type,creditcard', 'string', 'max:255'],
                'card_number' => ['required_if:type,creditcard', 'string'],
                'card_cvc' => ['required_if:type,creditcard', 'string', 'min:3'],
                'card_month' => ['required_if:type,creditcard', 'string','max:2', 'min:2'],
                'card_year' => ['required_if:type,creditcard', 'string','max:2', 'min:2'],
                'mobile' => ['required_if:type,stcpay', 'string', 'regex:/^05[0-9]{8}$/'],
            ];

            $approved_driver_status = 7;
            $under_paid = 12;
            $paid = 10;

            $under_review = 1;

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                $message = implode('<br>', $validator->errors()->all());
                return $this->sendRes($message, false, [], $validator->errors(), 400);
            }
            $call_back_url = route('payments.call-back');

            if($order->pay_status_id != $under_paid) {
                if($order->type == 'purchase') {
                    return $this->sendRes(__("drivers.driver did not approved"), false, [], [], 400);
                } else {
                    if($order->status_id == $under_review) {
                        return $this->sendRes(__("drivers.driver did not approved"), false, [], [], 400);
                    } else if($order->pay_status_id == $paid) {
                        return $this->sendRes(__("payments.payment paid before"), false, [], [], 400);
                    }

                    return $this->sendRes(__("invoices.invoice didn't created"), false, [], [], 400);
                }
            } else {
                // the invoice is under paid
                if($order->type == 'delivery') {
                    if(!isset($order->invoice)) {
                        return $this->sendRes(__("invoices.invoice didn't created"), false, [], [], 400);
                    }
                    $total = $order->invoice->total_amount;
                } else {
                    $total = $order->total_with_delivery;
                }
                $data = [
                    'currency' => "SAR",
                    'reference' => $order->reference,
                    'amount' => round($total),
                    'callback_url' => $call_back_url
                ];
                $type = $request->type;
                if($type == 'creditcard') {
                    $data['card_name'] = $request->card_name;
                    $data['card_number'] = $request->card_number;
                    $data['card_cvc'] = $request->card_cvc;
                    $data['card_month'] = $request->card_month;
                    $data['card_year'] = $request->card_year;
                } else if($type == 'stcpay') {
                    $data['mobile'] = $request->mobile;
                }
                $response = $this->moyasarService->make_payment($data, $type);
                if(isset($response['type'])) {
                    $message = '';
                    if(isset($response['errors'])) {
                        foreach ($response['errors'] as $key => $error) {
                            if(is_array($error)) {
                                foreach ($error as $er) {
                                    $message .= $er . "\n ";
                                }
                            }
                        }
                    }
                    return $this->sendRes($message, false, [], [], 400);
                }

                $payment_arr = [
                    'paid_user_id' => $user->id,
                    'payment_gateway_id' => $response['id'],
                    'amount' => $total,
                    'currency' => "SAR",
                    'description' => $response['description'],
                    'ip' => $response['ip'],
                    'source_type' => $response['source']['type'],
                    'status' => 'initiated',
                    'callback_url' => $call_back_url
                ];
                if(isset($response['source']['type']) && $response['source']['type'] == 'creditcard') {
                    $payment_arr['source_company'] = $response['source']['company'];
                    $payment_arr['source_name'] = $response['source']['name'];
                    $payment_arr['source_number'] = $response['source']['number'];

                } else if(isset($response['source']['type']) && $response['source']['type'] == 'stcpay') {
                    $payment_arr['source_mobile'] = $response['source']['mobile'];
                }

                $payment = $order->payments()->create($payment_arr);
                $data = [
                    'transaction_url' => $response['source']['transaction_url'],
                    'payment' => $payment,
                    'reference' => $order->reference,
                ];

                return $this->sendRes(__('payments.make a payment'), true, $data);
            }
        } else {
            return $this->sendRes(__('orders.not found'), false, [], [], 400);

        }
    }



    public function cancel(Request $request, $order_id)
    {

        $client = auth()->user();

        $lang = app()->getLocale();
        $status = "id,name_$lang as name,color,bg,border";
        $order = $client->orders()->with('client_location', "status:$status", "pay_status:$status")->find($order_id);

        if($order) {
            if($order->status_id == 6) {
                return $this->sendRes(__('orders.order is already canceled'), false, [], [], 400);
            }
            if($order->status_id == 1) {
                $order->status_id = 6;
                $order->save();
                return $this->sendRes(__('orders.order canceled success'), true);
            } else {
                return $this->sendRes(__('orders.you can not cancel your order'), false, [], [], 400);

            }
        } else {
            return $this->sendRes(__('orders.not found'), false, [], [], 400);
        }

    }



}
