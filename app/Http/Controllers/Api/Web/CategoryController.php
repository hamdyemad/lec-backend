<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\CategoryResource;
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
        $per_page = $request->get('per_page', 10);
        $keyword = $request->keyword ?? '';

        $categories = Category::with('translationsRelations')->latest(); // or your query with filters
        if($keyword) {
            $categories = $categories->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) {
                    $query->where('lang_key', "name");
                });
            });
        }
        $categories = $categories->latest()->paginate($per_page);
        $categories->getCollection()->transform(function ($category) {
            return new CategoryResource($category);
        });

        return $this->sendRes('all categories', true, $categories);
    }


    public function show(Request $request, $uuid) {
        $category = Category::where('uuid', $uuid)->first();
        $category = new CategoryResource($category);
        if(!$category) {
            return $this->sendRes(translate('cataegory not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('cataegory found'), true, $category);
    }



}
