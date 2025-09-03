<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreationEvent;
use App\Events\OrderFindEvent;
use App\Events\SendMessage;
use App\Http\Controllers\Controller;
use App\Http\Resources\Web\OrderResource;
use App\Models\ApiKey;
use App\Models\Cart;
use App\Models\Currency;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Message;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Service;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\User;
use App\Models\UserType;
use App\Rules\BelongsToProduct;
use App\Service\LogistiService;
use App\Service\MesagatService;
use App\Service\MoyasarService;
use App\Service\PushNotificaion;
use App\Service\WatsappService;
use App\Service\StripeService;
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function __construct(public WatsappService $watsappService, public StripeService $stripeService)
    {

    }
    public function index(Request $request)
    {

        $auth = auth()->user();

        $rules = [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:booked,history'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $type = $request->type ?? '';
        $per_page = $request->per_page ? $request->per_page : 10;

        $orders = $auth->orders()->with('status', 'items.product.specifications')->orderBy('created_at', 'desc');


        if($type) {
            if($type == 'booked') {
                $orders = $orders->whereHas('status', function($status) {
                    $status->where('type', 'processing');
                });
            } else {
                $orders = $orders->whereHas('status', function($status) {
                    $status->where('type', 'finished');
                });
            }
        }
        $orders = $orders->paginate($per_page);

        $orders->getCollection()->transform(function ($order) {
            return new OrderResource($order);
        });

        return $this->sendRes(translate('orders list'), true, $orders);
    }

    public function store(Request $request)
    {
        $auth = auth()->user();
        $reference = Order::latest()->first() ? Order::latest()->first()->reference + 1 : 1;
        $default_status = Status::where('default', 1)->first();

        if(!$default_status) {
            return $this->sendRes(translate('you should create a default status'), false, [], [], 400);
        }
        $creation_order_status = $default_status->id;
        $rules = [
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['required', 'exists:products,id'],
            'count' => ['required', 'array'],
            'count.*' => ['required', 'integer', 'min:1'],

            'color_ids' => ['required', 'array'],
            'color_ids.*' => ['required',  new BelongsToProduct('products_colors')],

            'addons' => ['nullable', 'array'],
            'addons.*' => ['nullable', 'array'],
            'addons.*.*' => ['required', new BelongsToProduct('products_addons')],
            'version_ids' => ['nullable', 'array'],
            'version_ids.*' => ['nullable', new BelongsToProduct('products_versions')],
            'delivery_location_id' => ['required', 'exists:countries,id'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ];


        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $message = implode('<br>', $messages);
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $data = [
            'uuid' => \Str::uuid(),
            'reference' => $reference,
            'status_id' => $creation_order_status,
            'client_id' => $auth->id,
            'delivery_location_id' => $request->delivery_location_id,
            'shipping_method_id' => $request->shipping_method_id,
            'payment_method_id' => $request->payment_method_id,
        ];

        $order = Order::create($data);
        $order->status_history()->attach($creation_order_status); // attach default status
        $totals = 0;
        foreach($request->product_ids as $index => $product_id) {
            $product = Product::findOrFail($product_id);
            $color_id = $request->color_ids[$index];
            $version_id = isset($request->version_ids[$index]) ? $request->version_ids[$index] : null;
            $addons = isset($request->addons[$index]) ? $request->addons[$index] : [];
            $count = $request->count[$index] ??  1;
            // Calculate the price based on version and addons
            $price = $version_id ? $product->versions()->find($version_id)->price : $product->price;
            $addons_price = 0;
            if ($addons) {
                foreach ($addons as $addon_id) {
                    $addon = $product->addons()->findOrFail($addon_id);
                    if($addon) {
                        $addons_price += $addon->price;
                    }
                }
            }
            $total = $addons_price + ($price * $count);
            $totals += $total;
            // Create order item
            $order->items()->create([
                'product_id' => $product_id,
                'color_id' => $color_id,
                'version_id' => $version_id,
                'addon_ids' => json_encode($addons),
                'count' => $count,
                'unit_price' => $price,
                'addons_price' => $addons_price,
                'total' => $addons_price + ($price * $count),
            ]);
        }

        // Calculate the shipping method
        $shipping_method = ShippingMethod::find($request->shipping_method_id);
        $shipping_value = 0;
        $grand_total = 0;
        if($shipping_method->type == 'number') {
            $grand_total = $totals + $shipping_method->value;
        } else if($shipping_method->type == 'percent') {
            $grand_total = $totals + ($totals *  ($shipping_method->value / 100));
        }



        $order->update([
            'total' => $totals,
            'shipping_type' => $shipping_method->type,
            'shipping_value' => $shipping_method->value,
            'grand_total' => $grand_total,
        ]);

         // If The Payment Method is Credit/Debit Card
        if($request->payment_method_id == 2) {
            $title_notify = __('orders.order created success please confirm your payment gateway');
            $paymentIntent = $this->stripeService->createPaymentIntent($grand_total);
            if($paymentIntent) {
                Payment::create([
                    'paid_user_id' => auth()->id(),
                    'order_id' => $order->id,
                    'payment_gateway_id' => $paymentIntent['id'],
                    'client_secret' => $paymentIntent['client_secret'],
                    'ip' => request()->ip(),
                    'amount' => $grand_total,
                    'currency' => 'usd'
                ]);
            }
        } else {
            $title_notify = __('orders.order created success');
        }


        // Send Notifications
        $auth->notifications()->create([
            'title' => $title_notify,
            'content' => $title_notify
        ]);

        $data = [
            'reference' => $order->reference,
            'payment_method' => $order->payment_method,
        ];
        if($order->payment_method->id == 2) {
            $data['stripe_public_key'] = env('STRIPE_PUBLIC_KEY');
            $data['payment'] = $order->payment;
        }

        return $this->sendRes($title_notify, true, $data);
    }



    public function show(Request $request, $uuid)
    {
        $client = auth()->user();

        $order = $client->orders()->where('uuid', $uuid)->first();
        if(!$order) {
            return $this->sendRes(translate('order not found'), false, [], [], 400);
        }

        $order->load(['items.product.specifications', 'status', 'delivery_location']);

        $order = new OrderResource($order);
        return $this->sendRes(translate('order data'), true, $order);

    }


    public function status_history(Request $request, $uuid)
    {
        $client = auth()->user();
        $order = $client->orders()->where('uuid', $uuid)->first();
        if(!$order) {
            return $this->sendRes(translate('order not found'), false, [], [], 400);
        }

        $per_page = request('per_page') ?? 10;
        $status_history = $order->status_history()->latest()->paginate($per_page);

        $data = [
            'status_history' => $status_history
        ];
        return $this->sendRes(translate('order status history'), true, $data);

    }

}
