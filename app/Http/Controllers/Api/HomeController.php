<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\CategoryResource;
use App\Http\Resources\Mobile\ProductResource;
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


        $keyword = $request->keyword ?? '';
        $per_page = $request->per_page ?? 12;


        // Category
        $categories = Category::latest();


        // Products
        $products = Product::with('specifications', 'versions', 'addons',
        'warrantlies', 'productColors')->latest();


        // Recently Products
        $recently_views_products = Product::with('specifications', 'versions', 'addons',
        'warrantlies', 'productColors')
        ->whereHas('recently_views', function($query) use ($auth) {
                $query->where('user_id', $auth->id);
        })->latest();


        if ($keyword) {
            $categories = $categories->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) {
                    $query->where('lang_key', "name");
                });
            });

            $products = $products->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) use($keyword) {
                    $query->where('lang_key', "title")
                    ->orWhere('lang_key', 'content');
                });
            });
            $recently_views_products = $recently_views_products->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) use($keyword) {
                    $query->where('lang_key', "title")
                    ->orWhere('lang_key', 'content');
                });
            });
            $auth->recent_searches()->updateOrCreate(
                ['keyword' => $keyword]
            );
        }


        $categories = $categories->paginate($per_page, ['*'], 'categories_page');
        $categories->getCollection()->transform(function ($category) {
            return new CategoryResource($category);
        });
        $products = $products->paginate($per_page, ['*'], 'products_page');
        $products->getCollection()->transform(function ($product) {
            return new ProductResource($product);
        });
        $recently_views_products = $recently_views_products->paginate($per_page, ['*'], 'recently_views_products_page');
        $recently_views_products->getCollection()->transform(function ($product) {
            return new ProductResource($product);
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
