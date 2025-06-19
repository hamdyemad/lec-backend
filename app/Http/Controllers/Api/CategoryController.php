<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Category;
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

class CategoryController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $categories = Category::latest();
        $per_page = $request->get('per_page', 12);
        $categories = $categories->paginate($per_page);

        return $this->sendRes('all categories', true, $categories);
    }


    public function show(Request $request, $uuid) {
        $category = Category::where('uuid', $uuid)->first();
        if(!$category) {
            return $this->sendRes(translate('cataegory not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('cataegory found'), true, $category);
    }



}
