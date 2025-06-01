<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FeatureType;
use App\Models\Product;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Location;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    use Res, FileUploads;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function home(Request $request)
    {
        $rules = [
            'keyword' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer'],
            'categories_page' => ['nullable', 'integer'],
            'products_page' => ['nullable', 'integer'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $keyword = $request->keyword;
        $per_page = $request->per_page ?? 12;


        $categories = Category::paginate($per_page, ['*'], 'categories_page');
        $products = Product::with('specifications', 'colors', 'versions')
                        ->paginate($per_page, ['*'], 'products_page');

        $data = [
            'categories' => $categories,
            'products' => $products,
        ];


        $message = translate('home page data');
        return $this->sendRes($message, true, $data);

    }


    public function getRate($seller) {
        $seller_rates = collect($seller->seller_rates);
        $avg_rates = $seller_rates->avg('rate');
        $seller->average_rate = round($avg_rates, 1);
        unset($seller->seller_rates);
        return $seller;
    }

    public function seller_products(Request $request, $seller_id)
    {
        $auth = auth()->user();
        $select = ['id', 'uuid', 'type', 'active','status', 'username', 'image', 'email', 'dateOfBirth',
        'mobile', 'mobile_code', 'category_id', 'latitude', 'longitude', 'created_at', 'updated_at'];
        $seller = User::select($select)->where([
            'id' => $seller_id,
            'type' => $this->seller_type
        ])
        ->first();

        if($seller) {
            $this->getRate($seller);
            $paginate = $request->paginate;
            $rules = [
                'paginate' => ['nullable', 'integer'],
            ];
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                $message = implode('<br>', $validator->errors()->all());
                return $this->sendRes($message, false, [], $validator->errors(), 400);
            }



            if($paginate) {
                $seller->products = $seller->products()->where('status', 1)->with('items')->paginate($paginate);
            } else {
                $seller->products = $seller->products()->where('status', 1)->with('items')->get();
            }

            $seller->products->map(function($product) use($auth) {
                $product->cart_counter = 0;
                $product->is_favorite = 0;
                foreach ($auth->favorite_products as $favorite_product) {
                    if($favorite_product->product_id == $product->id) {
                        $product->is_favorite = 1;
                    }
                }
                foreach ($auth->carts as $cart) {
                    if($cart->product_id == $product->id) {
                        $product->cart_counter = $cart->count;
                    }
                }
                return $product;
            });


            return $this->sendRes(__('validation.success'), true, $seller);

        } else {
            return $this->sendRes(__('main.not found'), false, [], [], 400);
        }


    }




}
