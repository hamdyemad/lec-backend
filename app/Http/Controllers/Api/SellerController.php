<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SellerController extends Controller
{
    use Res, FileUploads;


    public function show(Request $request, $seller_id) {

        return $seller_id;
        $rules = [
            'paginate' => ['nullable', 'integer'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $paginate = $request->paginate;

        // Products
        $products = Product::latest();
        if($paginate) {
            $products = $products->paginate($paginate);
        } else {
            $products = $products->get();
        }

        // Categories

        $feature_type = FeatureType::where('name', 'category')->first();
        $categories = $feature_type->features()->select(['id', 'feature','title', 'image'])->latest();
        $categories = $categories->get();

        $data = [
            'categories' => $categories,
            'products' => $products,
        ];

        return $this->sendRes(__('products.index'), true, $data);

    }



}
