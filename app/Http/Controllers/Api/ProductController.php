<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Product;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function index(Request $request)
    {
        $authUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'recently_views' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }
        $keyword = $request->keyword ?? '';
        $per_page = $request->per_page ?? 12;
        $recently_views = $request->recently_views ?? false;
        $category_id = $request->category_id ?? '';
        $from_price = $request->from_price ?? '';
        $to_price = $request->to_price ?? '';
        $specification_id = $request->specification_id ?? '';

        $products = Product::with('specifications', 'versions', 'colors')->latest();

        if($recently_views) {
            $products = $products->whereHas('recently_views', function($query) use ($authUser) {
                    $query->where('user_id', $authUser->id);
            })->latest();
        }

        if($specification_id) {
             $products = $products->whereHas('specifications', function($query) use($specification_id) {
                $query->where('specifications.id', $specification_id);
             });
        }
        if($category_id) {
            $products = $products->where('category_id', $category_id);
        }
        if($keyword) {
            $products = $products
            ->where('title', 'like', "%$keyword%")
            ->orWhere('content', 'like', "%$keyword%");

            $authUser->recent_searches()->updateOrCreate(
                ['keyword' => $keyword]
            );
        }

        if($from_price) {
            $products = $products->where('price', '>=', $from_price);
        }
        if($to_price) {
            $products = $products->where('price', '<=', $to_price);
        }

        $products = $products->paginate($per_page);

        $products->map(function ($product) use($authUser) {
            $baseCurrency = \App\Models\Currency::where('base_currency', 1)->first();
            ($baseCurrency) ? $product->base_currency = $baseCurrency->symbol : null;

            // Active Product To User
            (in_array($product->id,$authUser->favorite_products()->pluck('product_id')->toArray())) ? $active = true : $active = false;
            $product->active = $active;
            return $product;
        });

        return $this->sendRes(translate('products data'), true, $products);
    }


    public function show(Request $request, $uuid) {
        $authUser = auth()->user();
        $product = Product::with('specifications', 'versions', 'colors', 'addons', 'warrantlies')->where('uuid', $uuid)->first();
        if(!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);
        }

        // Active Product To User
        (in_array($product->id,$authUser->favorite_products()->pluck('product_id')->toArray())) ? $active = true : $active = false;
        $product->active = $active;


        $baseCurrency = \App\Models\Currency::where('base_currency', 1)->first();
        ($baseCurrency) ? $product->base_currency = $baseCurrency->symbol : null;

        $product->recently_views()->sync(auth()->id()); // Add to recently viewed
        return $this->sendRes(translate('product found'), true, $product);
    }


}
