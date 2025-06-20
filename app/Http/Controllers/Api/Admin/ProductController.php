<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Language;
use App\Models\Product;
use App\Models\Translation;
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
        $authUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'per_page' => ['nullable', 'integer', 'min:1'],
            'recently_views' => ['nullable', 'boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        $keyword = $request->keyword ?? '';
        $per_page = $request->per_page ?? 12;
        $recently_views = $request->recently_views ?? false;
        $category_id = $request->category_id ?? '';
        $from_price = $request->from_price ?? '';
        $to_price = $request->to_price ?? '';

        $products = Product::with('specifications', 'versions', 'colors')->latest();

        if ($recently_views) {
            $products = $products->whereHas('recently_views', function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })->latest();
        }

        if ($category_id) {
            $products = $products->where('category_id', $category_id);
        }
        if ($keyword) {
            $products = $products
                ->where('title', 'like', "%$keyword%")
                ->orWhere('content', 'like', "%$keyword%");

            $authUser->recent_searches()->updateOrCreate(
                ['keyword' => $keyword]
            );
        }

        if ($from_price) {
            $products = $products->where('price', '>=', $from_price);
        }
        if ($to_price) {
            $products = $products->where('price', '<=', $to_price);
        }

        $products = $products->paginate($per_page);

        $products->map(function ($product) {
            $baseCurrency = \App\Models\Currency::where('base_currency', 1)->first();
            ($baseCurrency) ? $product->base_currency = $baseCurrency->symbol : null;
            $product->title = $product->translate('title');
            $product->content = $product->translate('content');
            return $product;
        });

        return $this->sendRes(translate('products data'), true, $products);
    }


    public function form(Request $request, $product = null)
    {

        $rules = [

            // Product Translations
            'translations' => ['required', 'array'],
            'translations.*' => ['required', 'array'],
            'translations.*.lang' => ['required', 'exists:languages,id'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.content' => ['required', 'string'],

            'price' => ['required', 'numeric'],
            'category_id' => ['required', 'exists:categories,id'],

            // Colors
            'colors' => ['required', 'array'],
            'colors.*.lang' => ['required', 'string', 'max:255'],
            'colors.*.name' => ['required', 'string', 'max:255'],
            'colors.*.value' => ['required', 'string', 'max:255'],
            // Images
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'max:2048'],

            // Images
            'structural_images' => ['nullable', 'array'],
            'structural_images.*' => ['nullable', 'image', 'max:2048'],

            // Specifications
            'specifications' => ['nullable', 'array'],
            'specifications.*' => [Rule::exists('specifications', 'id')],

            // Warrantly
            'warrantly' => ['nullable', 'array'],
            'warrantly.*.type' => ['required_with:warrantly.*.title', 'string', 'max:255', 'in:km,battery'],
            'warrantly.*.title' => ['required_with:warrantly.*.type', 'string', 'max:255'],

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
        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $data = [
            'category_id' => $request->category_id,
            'price' => $request->price,
        ];



        if (isset($request->images)) {
            // Remove old images if they exist
            if ($product && $product->images) {
                $oldImages = json_decode($product->images, true);
                foreach ($oldImages as $oldImage) {
                    if (file_exists(public_path($oldImage))) {
                        unlink(public_path($oldImage));
                    }
                }
            }
            $images = [];
            foreach ($request->images as $image) {
                $imagePath = $this->uploadFiles($image, $this->products_path);
                if ($imagePath) {
                    $images[] = $imagePath;
                }
            }
            $data['images'] = json_encode($images);
        }

        if (isset($request->structural_images)) {
            // Remove old images if they exist
            if ($product && $product->structural_images) {
                $oldstructural_images = json_decode($product->structural_images, true);
                foreach ($oldstructural_images as $oldImage) {
                    if (file_exists(public_path($oldImage))) {
                        unlink(public_path($oldImage));
                    }
                }
            }
            $images = [];
            foreach ($request->structural_images as $image) {
                $imagePath = $this->uploadFiles($image, $this->products_path);
                if ($imagePath) {
                    $images[] = $imagePath;
                }
            }
            $data['structural_images'] = json_encode($images);
        }


        if ($product) {
            $message = translate('product updated successfully');
            $product->specifications()->detach(); // Detach old specifications
            $product->versions()->delete(); // Delete old versions
            $product->colors()->delete();
            $product->addons()->delete();
            $product->warrantlies()->delete();


            $product->update($data);
        } else {
            $message = translate('product addedd successfully');
            $data['uuid'] = \Str::uuid();
            $product = Product::create($data);
        }

        if ($request->versions) {
            foreach ($request->versions as $version) {
                $product->versions()->create([
                    'name' => $version['name'],
                    'price' => $version['price'],
                ]);
            }
        }

        if ($request->specifications) {
            $product->specifications()->sync($request->specifications);
        }

        if ($request->colors) {
            foreach ($request->colors as $color) {
                $product->colors()->create([
                    'name' => $color['name'],
                    'value' => $color['value'],
                ]);
            }
        }


        if ($request->addons) {
            foreach ($request->addons as $addon) {
                $product->addons()->create([
                    'name' => $addon['name'],
                    'price' => $addon['price'],
                ]);
            }
        }

        if ($request->warrantly) {
            foreach ($request->warrantly as $warrantly) {
                $product->warrantlies()->create([
                    'type' => $warrantly['type'],
                    'title' => $warrantly['title'],
                ]);
            }
        }


        // Product Translations
        if($request->translations) {
            foreach($request->translations as  $translation) {
                foreach (['title', 'content'] as $key) {
                    Translation::create([
                        'translatable_model' => Product::class,   // ✅ fix: not "translatable_model"
                        'translatable_id'   => $product->id,
                        'lang_id'           => $translation['lang'],
                        'lang_key'               => $key,
                        'lang_value'             => $translation[$key],
                    ]);
                }
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
        if (!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);
        }
        return $this->form($request, $product);
    }


    public function show(Request $request, $uuid)
    {
        $product = Product::with('colors')->where('uuid', $uuid)->first();

        if (!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);
        }

        // Set base currency
        $baseCurrency = \App\Models\Currency::where('base_currency', 1)->first();
        if ($baseCurrency) {
            $product->base_currency = $baseCurrency->symbol;
        }

        // Add to recently viewed
        $product->recently_views()->sync(auth()->id());

        $translations = Language::with(['translations' => function ($q) {
            $q->where('translatable_model', Product::class);
        }])->get();
        $product->translations = $translations;


        return $this->sendRes(translate('product found'), true, $product);
    }

    public function delete(Request $request, $uuid)
    {
        $product = Product::where('uuid', $uuid)->first();
        if (!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);
        }
        // Remove old images if they exist
        if ($product && $product->images) {
            $oldImages = json_decode($product->images, true);
            foreach ($oldImages as $oldImage) {
                if (file_exists(public_path($oldImage))) {
                    unlink(public_path($oldImage));
                }
            }
        }
        $product->specifications()->detach(); // Detach old specifications
        $product->versions()->delete(); // Delete old versions
        $product->delete();
        return $this->sendRes(translate('product deleted successfully'), true);
    }
}
