<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\FavoriteProduct;
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

class FavoriteProductController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $favorite_products = $user->favorite_products()->with('product', 'product.specifications', 'product.colors', 'product.versions')->paginate(12);
        return $this->sendRes(translate('favorite products data'), true, $favorite_products);
    }


    public function store(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'product_id' => ['required', 'exists:products,id'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $finded_favorite = $user->favorite_products()->where('product_id', $request->product_id)->first();
        if($finded_favorite) {
            $message = translate('favorite product has been removed');
            $finded_favorite->delete();
        } else {
            $message = translate('favorite product has been added');
            $user->favorite_products()->create([
                'product_id' => $request->product_id
            ]);
        }

        return $this->sendRes($message, true, [], [], 200);

    }



}
