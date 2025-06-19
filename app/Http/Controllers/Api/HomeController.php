<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Currency;
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
        $auth = auth()->user();
        $rules = [
            'keyword' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer'],
            'categories_page' => ['nullable', 'integer'],
            'products_page' => ['nullable', 'integer'],
            'recently_views_products_page' => ['nullable', 'integer'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $keyword = $request->keyword;
        $per_page = $request->per_page ?? 12;

        $baseCurrency = Currency::where('base_currency', 1)->first();

        $categories = Category::latest();
        if($keyword) {
            $categories = $categories
            ->where('name', 'like', "%$keyword%");
        }
        $categories = $categories->paginate($per_page, ['*'], 'categories_page');

        // Products
        $products = Product::with('specifications', 'versions')->latest();


        if($keyword) {
            $products = $products
            ->where('title', 'like', "%$keyword%")
            ->orWhere('content', 'like', "%$keyword%");
        }

        $products = $products->paginate($per_page, ['*'], 'products_page');
        $products->map(function ($product) use($baseCurrency, $auth) {
            ($baseCurrency) ? $product->base_currency = $baseCurrency->symbol : null;

            // Active Product To User
            (in_array($product->id,$auth->favorite_products()->pluck('product_id')->toArray())) ? $active = true : $active = false;
            $product->active = $active;
            return $product;
        });

        // Recently Products
        $recently_views_products = Product::with(['specifications', 'versions'])
        ->whereHas('recently_views', function($query) use ($auth) {
                $query->where('user_id', $auth->id);
        })->latest();
        if($keyword) {
            $recently_views_products = $recently_views_products
            ->where('title', 'like', "%$keyword%")
            ->orWhere('content', 'like', "%$keyword%");
        }
        $recently_views_products = $recently_views_products->paginate($per_page, ['*'], 'recently_views_products_page');
        $recently_views_products->map(function ($product)  use($baseCurrency, $auth)  {
            ($baseCurrency) ? $product->base_currency = $baseCurrency->symbol : null;
            // Active Product To User
            (in_array($product->id,$auth->favorite_products()->pluck('product_id')->toArray())) ? $active = true : $active = false;
            $product->active = $active;
            return $product;
        });

        $data = [
            'categories' => $categories,
            'products' => $products,
            'recently_views_products' => $recently_views_products,
        ];


        $message = translate('home page data');
        return $this->sendRes($message, true, $data);

    }



    public function recent_searches() {
        $authUser = auth()->user();
        $recent_searches = $authUser->recent_searches()->latest()->paginate(10);
        return $this->sendRes(translate('recent searches data'), true, $recent_searches);
    }

    public function remove_recent_searches() {
        $authUser = auth()->user();
        $recent_searches = $authUser->recent_searches()->delete();
        return $this->sendRes(translate('deleted searches data successfully'), true);
    }



}
