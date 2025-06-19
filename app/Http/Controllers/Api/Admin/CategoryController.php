<?php

namespace App\Http\Controllers\Api\Admin;

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


    public function form(Request $request, $category = null) {

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category ? $category->id : null)],
            'image' => ['nullable', 'image', 'max:2048'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [
            'name' => $request->name,
        ];

        if(isset($request->image)) {
            if($category) {
                (file_exists($category->image)) ? unlink($category->image) : '';
            }
            $image = $this->uploadFile($request, $this->categories_path, 'image');
            $data['image'] = $image;
        }

        if($category) {
            $message = translate('category updated successfully');
            $category->update($data);
        } else {
            $message = translate('category added successfully');

            $data['uuid'] = \Str::uuid();
            $category = Category::create($data);
        }

        return $this->sendRes($message, true, $category);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $category = Category::where('uuid', $uuid)->first();
        if(!$category) {
            return $this->sendRes(translate('cataegory not found'), false, [], [], 400);
        }
        return $this->form($request, $category);

    }


    public function show(Request $request, $uuid) {
        $category = Category::where('uuid', $uuid)->first();
        if(!$category) {
            return $this->sendRes(translate('cataegory not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('cataegory found'), true, $category);
    }


    public function delete(Request $request, $uuid) {
        $category = Category::where('uuid', $uuid)->first();
        if(!$category) {
            return $this->sendRes(translate('cataegory not found'), false, [], [], 400);
        }
        (file_exists($category->image)) ? unlink($category->image) : '';
        $category->delete();
        return $this->sendRes(translate('cataegory deleted successfully'), true, [], [], 200);
    }




}
