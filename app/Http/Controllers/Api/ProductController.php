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
        $products = Product::with('specifications', 'versions', 'colors')->latest();
        $per_page = $request->get('per_page', 12);
        $products = $products->paginate($per_page);

        return $this->sendRes(translate('products data'), true, $products);
    }


    public function form(Request $request, $product = null) {

        $rules = [
            'title' => ['required', 'string', 'max:255',
            Rule::unique('products', 'title')->ignore($product ? $product->id : null)],
            'content' => ['nullable','string'],
            'price' => ['required','numeric'],
            'category_id' => ['required', 'exists:categories,id'],

            // Colors
            'colors' => ['required', 'array'],
            'colors.*.name' => ['required', 'string', 'max:255'],
            'colors.*.value' => ['required', 'string', 'max:255'],
            // Images
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'max:2048'],
            // Specifications
            'specifications' => ['nullable', 'array'],
            'specifications.*' => [Rule::exists('specifications', 'id')],
            // Versions
            'versions' => ['required', 'array'],
            'versions.*.name' => ['required', 'string', 'max:255'],
            'versions.*.price' => ['required', 'numeric'],
            // Addons
            'addons' => ['nullable', 'array'],
            'addons.*.name' => ['nullable', 'string', 'max:255'],
            'addons.*.price' => ['nullable', 'numeric'],

        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $data = [
            'title' => $request->title,
            'content' => $request->content,
            'category_id' => $request->category_id,
            'price' => $request->price,
        ];

        if(isset($request->images)) {
            // Remove old images if they exist
            if($product && $product->images) {
                $oldImages = json_decode($product->images, true);
                foreach($oldImages as $oldImage) {
                    if(file_exists(public_path($oldImage))) {
                        unlink(public_path($oldImage));
                    }
                }
            }
            $images = [];
            foreach($request->images as $image) {
                $imagePath = $this->uploadFiles($image, $this->products_path);
                if($imagePath) {
                    $images[] = $imagePath;
                }
            }
            $data['images'] = json_encode($images);
        }


        if($product) {
            $message = translate('product updated successfully');
            $product->specifications()->detach(); // Detach old specifications
            $product->versions()->delete(); // Delete old versions
            $product->colors()->delete();
            $product->update($data);
        } else {
            $message = translate('product addedd successfully');
            $data['uuid'] = \Str::uuid();
            $product = Product::create($data);
        }

        if($request->versions) {
            foreach($request->versions as $version) {
                $product->versions()->create([
                    'name' => $version['name'],
                    'price' => $version['price'],
                ]);
            }
        }

        if($request->specifications) {
            $product->specifications()->sync($request->specifications);
        }

        if($request->colors) {
            foreach($request->colors as $color) {
                $product->colors()->create([
                    'name' => $color['name'],
                    'value' => $color['value'],
                ]);
            }
        }

        return $this->sendRes($message, true, $product);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $product = Product::where('uuid', $uuid)->first();
        if(!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);
        }
        return $this->form($request, $product);

    }


    public function show(Request $request, $uuid) {
        $product = Product::with('specifications', 'versions', 'colors')->where('uuid', $uuid)->first();
        if(!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);
        }
        return $this->sendRes(translate('product found'), true, $product);
    }


    public function delete(Request $request, $uuid) {
        $product = Product::where('uuid', $uuid)->first();
        if(!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);

        }
        // Remove old images if they exist
        if($product && $product->images) {
            $oldImages = json_decode($product->images, true);
            foreach($oldImages as $oldImage) {
                if(file_exists(public_path($oldImage))) {
                    unlink(public_path($oldImage));
                }
            }
        }
        $product->specifications()->detach(); // Detach old specifications
        $product->versions()->delete(); // Delete old versions
        $product->delete();
        return $this->sendRes(translate('product deleted successfully'), true, $product);

    }




}
