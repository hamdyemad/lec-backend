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
