<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Status;
use App\Service\FirebaseService;
use App\Service\WatsappService;
use App\Traits\Delivery;
use App\Traits\FileUploads;
use App\Traits\Location;
use App\Traits\Res;
use Exception;
use Illuminate\Http\Request;
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


    public function __construct(public WatsappService $watsappService, public FirebaseService $firebaseService) {}
    public function index(Request $request)
    {


        $rules = [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $per_page = $request->per_page ? $request->per_page : 12;

        $orders = Order::with(['items.product.specifications', 'items.product.productColors', 'payment_method', 'payment', 'status', 'delivery_location', 'shipping_method'])->latest();
        $orders = $orders->paginate($per_page);

        $orders->getCollection()->transform(function ($order) {
            $baseCurrency = Currency::where('base_currency', 1)->first();
            ($baseCurrency) ? $order->base_currency = $baseCurrency->symbol : null;
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->title = $item->product->translate('title');
                    $item->product->content = $item->product->translate('content');
                    $item->product->colors = $item->product->productColors;
                    if ($item->product->productColors) {
                        unset($item->product->productColors);
                    }
                    if($item->product->specifications) {
                        foreach ($item->product->specifications as $specification) {
                            $specification->header = $specification->translate('header');
                            $specification->body = $specification->translate('body');
                        }
                    }
                    if($item->product->colors) {
                        foreach ($item->product->colors as $color) {
                            $color->name = $color->translate('name');
                        }
                    }
                }
            }
            return $order;
        });


        return $this->sendRes(translate('orders list'), true, $orders);
    }




    public function show(Request $request, $uuid)
    {
        $order = Order::with([
            'items.product.specifications',
            'items.product.productColors',
            'payment_method', 'payment',
            'status',
            'delivery_location',
            'shipping_method'
        ])->where('uuid', $uuid)->first();
        if (!$order) {
            return $this->sendRes(translate('order not found'), false, [], [], 400);
        }
        $per_page = request('per_page') ?? 12;
        $status_history = $order->status_history()->latest()->paginate($per_page);



        $baseCurrency = Currency::where('base_currency', 1)->first();
        ($baseCurrency) ? $order->base_currency = $baseCurrency->symbol : null;
        foreach ($order->items as $item) {
            if($item->product) {
                $item->product->title = $item->product->translate('title');
                $item->product->content = $item->product->translate('content');
                $item->product->colors = $item->product->productColors;
                unset($item->product->productColors);
                if($item->product->specifications) {
                    foreach ($item->product->specifications as $specification) {
                        $specification->header = $specification->translate('header');
                        $specification->body = $specification->translate('body');
                    }
                }
                if($item->product->colors) {
                    foreach ($item->product->colors as $color) {
                        $color->name = $color->translate('name');
                    }
                }
            }
        }


        $data = [
            'order' => $order,
            'status_history' => $status_history
        ];
        return $this->sendRes(translate('order data'), true, $data);
    }


    public function update_status(Request $request, $uuid)
    {
        $order = Order::where('uuid', $uuid)->first();
        if (!$order) {
            return $this->sendRes(translate('order not found'), false, [], [], 400);
        }
        $rules = [
            'status' => ['required', 'exists:tb_status,id'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $status = Status::find($request->status);
        if ($order->client->device_token) {
            $token = $order->client->device_token->token;
            $title = translate('order status');
            $body = translate('order status changed success to (' . $status->name . ')');
            $otherData = [
                'order_uuid' => $order->uuid
            ];
            try {
                $this->firebaseService->send_notification($token, $title, $body, $otherData);
            } catch (Exception $e) {
            }
        }
        $order->status_history()->attach($request->status);
        $order->update([
            'status_id' => $request->status
        ]);

        $message = translate('order status changed success');
        // Start Send Notifications
        Notification::create([
            'user_id' => $order->client_id,
            'title' => translate('order number:') . $order->reference,
            'content' => $message
        ]);
        // End Send Notifications

        return $this->sendRes($message, true);
    }
}
