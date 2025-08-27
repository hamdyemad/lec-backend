<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\ProductResource;
use App\Models\FavoriteProduct;
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
        $per_page = request('per_page') ?? 12;
        $keyword = request('keyword') ?? '';
        $product_ids = $user->favorite_products()->pluck('product_id')->toArray();
        $favorite_products = Product::whereIn('id', $product_ids)
        ->with('specifications', 'versions', 'addons', 'warrantlies', 'productColors', 'translationsRelations');


        if ($keyword) {
            $favorite_products = $favorite_products->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) use($keyword) {
                    $query->where('lang_key', "title")
                    ->orWhere('lang_key', 'content');
                });
            });
            $user->recent_searches()->updateOrCreate(
                ['keyword' => $keyword]
            );
        }

        $favorite_products = $favorite_products->paginate($per_page);


        $favorite_products->getCollection()->transform(function($product) {
            return new ProductResource($product);
        });

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
