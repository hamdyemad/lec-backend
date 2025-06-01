<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

class SubCategoryController extends Controller
{
    use Res, FileUploads;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $category_id)
    {
        $feature_type = FeatureType::where('name', 'category')->first();
        $category = $feature_type->features()->select(['id', 'feature','title', 'image'])->find($category_id);
        if($category) {
            $feature_type = FeatureType::where('name', 'subcategory')->first();
            $sub_categories = $feature_type->features()
            ->where('category', $category_id)
            ->select(['id', 'feature','category','title', 'image'])->get();

            return $this->sendRes('all subcategories', true, $sub_categories);

        } else {
            return $this->sendRes(__('categories.not found'), false, [], [], 400);
        }
        $feature_type = FeatureType::where('name', 'subcategory')->first();
        $categories = $feature_type->features()->select(['id', 'feature','title', 'image'])->get();
        return $this->sendRes('all categories', true, $categories);
    }


    public function store(Request $request, $category_id)
    {
        $rules = [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $title = serialize([
            'ar' => $request->name_ar,
            'en' => $request->name_en,
        ]);

        $feature_type = FeatureType::where('name', 'subcategory')->first();

        $data = [
            'title' => $title,
            'category' => $category_id,
        ];


        if(isset($request->image)) {
            $image = $this->uploadFile($request, $this->categories_path, 'image');
            $data['image'] = $image;
        }

        $category = $feature_type->features()->create($data);

        return $this->sendRes(__('categories.add success'), true, $category);
    }

    public function edit(Request $request, $category_id, $sub_category_id)
    {
        $feature_type = FeatureType::where('name', 'category')->first();
        $category = $feature_type->features()->select(['id', 'feature','title', 'image'])->find($category_id);
        if($category) {

            $feature_type = FeatureType::where('name', 'subcategory')->first();
            $sub_category = $feature_type->features()->select(['id', 'feature','category','title', 'image'])->find($sub_category_id);
            if($sub_category) {
                $rules = [
                    'name_en' => ['required', 'string', 'max:255'],
                    'name_ar' => ['required', 'string', 'max:255'],
                ];

                $validator = Validator::make($request->all(), $rules);
                if($validator->fails()) {
                    $message = implode('<br>', $validator->errors()->all());
                    return $this->sendRes($message, false, [], $validator->errors(), 400);
                }

                $title = serialize([
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ]);

                $data = [
                    'title' => $title,
                ];

                if(isset($request->image)) {
                    (file_exists($sub_category->image)) ? unlink($sub_category->image) : '';
                    $image = $this->uploadFile($request, $this->categories_path . '/' . "$category->id/sub_categories/" , 'image');
                    $data['image'] = $image;
                }
                $sub_category->update($data);
                return $this->sendRes(__('subcategories.update success'), true, $sub_category);
            } else {
                return $this->sendRes(__('subcategories.not found'), false, [], [], 400);
            }

        } else {
            return $this->sendRes(__('categories.not found'), false, [], [], 400);
        }
    }

    public function show(Request $request, $id, $sub_id) {
        $feature_type = FeatureType::where('name', 'category')->first();
        $category = $feature_type->features()->select(['id', 'feature','title', 'image'])->find($id);
        if($category) {
            $feature_type = FeatureType::where('name', 'subcategory')->first();
            $sub_category = $feature_type->features()
            ->where('category', $id)
            ->where('id', $sub_id)
            ->select(['id', 'feature','category','title', 'image'])->first();
            if($sub_category) {
                return $this->sendRes(__('subcategories.success'), true, $sub_category);
            } else {
                return $this->sendRes(__('subcategories.not found'), false, [], [], 400);
            }


        } else {
            return $this->sendRes(__('categories.not found'), false, [], [], 400);
        }
    }



    public function delete(Request $request, $id, $sub_id)
    {
        $feature_type = FeatureType::where('name', 'category')->first();
        $category = $feature_type->features()->find($id);
        if($category) {
            $feature_type = FeatureType::where('name', 'subcategory')->first();
            $sub_category = $feature_type->features()->select(['id', 'feature','category','title', 'image'])->find($sub_id);
            if($sub_category) {
                $sub_category->delete();
                return $this->sendRes(__('subcategories.delete success'), true, $sub_category);
            } else {
                return $this->sendRes(__('subcategories.not found'), false, [], [], 400);
            }


        } else {
            return $this->sendRes(__('categories.not found'), false, [], [], 400);
        }
    }



}
