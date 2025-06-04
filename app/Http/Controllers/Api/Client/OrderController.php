<?php

namespace App\Http\Controllers\Api\Client;

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
use App\Models\Product;
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function __construct() {}
    public function index(Request $request)
    {

        $auth = auth()->user();

        $rules = [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $per_page = $request->per_page ? $request->per_page : 10;

        $orders = $auth->orders()->with(['items.product.specifications', 'status', 'delivery_location', 'shipping_method'])->orderBy('created_at', 'desc');

        // if ($request->status_id) {
        //     $status_id = explode(',', $request->status_id);
        //     $orders = $orders->whereIn('status_id', $status_id);
        // }

        $orders = $orders->paginate($per_page);

        return $this->sendRes(translate('orders list'), true, $orders);
    }

    public function store(Request $request)
    {
        $auth = auth()->user();
        $reference = Order::latest()->first() ? Order::latest()->first()->reference + 1 : 1;
        $creation_order_status = 1;
        $user = auth()->user();
        $rules = [
            'product_id' => ['required', 'exists:products,id'],
            'color_id' => ['required', Rule::exists('products_colors', 'id')->where(function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['required', Rule::exists('products_addons', 'id')->where(function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })],
            'version_id' => ['nullable', Rule::exists('products_versions', 'id')->where(function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })],
            'delivery_location_id' => ['required', 'exists:countries,id'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'card_id' => ['required', Rule::exists('users_cards', 'id')->where(function ($query) use ($auth) {
                $query->where('user_id', $auth->id);
            })],
        ];


        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }


        $data = [
            'uuid' => \Str::uuid(),
            'reference' => $reference,
            'status_id' => $creation_order_status,
            'client_id' => $user->id,
            'delivery_location_id' => $request->delivery_location_id,
            'shipping_method_id' => $request->shipping_method_id,
            'card_id' => $request->card_id,
        ];

        $product = Product::findOrFail($request->product_id);
        $order = Order::create($data);

        $price = $request->version_id ? $product->versions()->find($request->version_id)->price : $product->price;

        $order->items()->create([
            'product_id' => $request->product_id,
            'color_id' => $request->color_id,
            'version_id' => $request->version_id ? $request->version_id : null,
            'addon_ids' => $request->addons ? json_encode($request->addons) : null,
            'count' => 1,
            'price' => $price,
            'total' => $price,
        ]);

        $title_notify = translate('order created');
        // $icon = '';
        // $notify_data = [
        //     'order_id' => $order->id,
        //     'order_type' => $order->type,
        //     'sound' => 'notification_sound_order'
        // ];
        // $res = $this->pushNotificaion
        //         ->sendPushNotificationByUsers($drivers_ids, $title_notify, $body_notify, $icon, $notify_data);

        $order->load(['items', 'status']);
        return $this->sendRes($title_notify, true, $order);


    }



    public function show(Request $request, $uuid)
    {
        $client = auth()->user();

        $order = $client->orders()->where('uuid', $uuid)->first();
        if(!$order) {
            return $this->sendRes(translate('order not found'), false, [], [], 400);
        }

        $order->load(['items.product.specifications', 'status', 'delivery_location', 'shipping_method']);
        return $this->sendRes(translate('order data'), true, $order);

    }

}
