<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductColorResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Product\ProductShowResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductColor;
use App\Models\ProductVersion;
use App\Models\ProductWarrantly;
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


        $products = Product::with('translationsRelations');

        if ($recently_views) {
            $products = $products->whereHas('recently_views', function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })->latest();
        }

        if ($category_id) {
            $products = $products->where('category_id', $category_id);
        }

        if ($keyword) {
            $products = $products->whereHas('translationsRelations', function ($q) use ($keyword) {
                $q->Where('lang_value', 'like', "%{$keyword}%")
                ->where(function($query) use($keyword) {
                    $query->where('lang_key', "title")
                    ->orWhere('lang_key', 'content');
                });
            });
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

        $products = $products->latest()->paginate($per_page);

        $products->load('specifications', 'versions', 'productColors');
        $products->getCollection()->transform(function ($item) {
            return new ProductResource($item);
        });


        return $this->sendRes(translate('products data'), true, $products);
    }


    public function form(Request $request, $product = null)
    {

        $rules = [

            // Product Translations
            'translations' => ['required', 'array'],
            'translations.*' => ['required', 'array'],
            'translations.*.lang_id' => ['required', 'exists:languages,id'],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.content' => ['required', 'string'],

            'price' => ['required', 'numeric'],
            'category_id' => ['required', 'exists:categories,id'],

            // Colors
            'colors' => ['required', 'array'],
            'colors.*.lang_id' => ['required', 'exists:languages,id'],
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
            'warrantly.*.lang_id' => ['required_with:warrantly', 'exists:languages,id'],
            'warrantly.*.type' => ['required_with:warrantly', 'string', 'max:255', 'in:km,battery'],
            'warrantly.*.title' => ['required_with:warrantly', 'string', 'max:255'],

            // Versions
            'versions' => ['required', 'array'],
            'versions.*.lang_id' => ['required', 'exists:languages,id'],
            'versions.*.name' => ['required', 'string', 'max:255'],
            'versions.*.price' => ['required', 'numeric'],
            // Addons
            'addons' => ['nullable', 'array'],
            'addons.*.lang_id' => ['required_with:addons', 'exists:languages,id'],
            'addons.*.name' => ['required_with:addons', 'string', 'max:255'],
            'addons.*.price' => ['required_with:addons', 'numeric'],

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

            // Start Remove Translations
            if($product->versions()->count() > 0) {
                foreach($product->versions as $version) {
                    Translation::where([
                        'translatable_model' => ProductVersion::class,
                        'translatable_id'   => $version->id,
                    ])->delete();
                }
            }
            if($product->productColors()->count() > 0) {
                foreach($product->productColors as $color) {
                    Translation::where([
                        'translatable_model' => ProductColor::class,
                        'translatable_id'   => $color->id,
                    ])->delete();
                }
            }
            if($product->addons()->count() > 0) {
                foreach($product->addons as $addon) {
                    Translation::where([
                        'translatable_model' => ProductAddon::class,
                        'translatable_id'   => $addon->id,
                    ])->delete();
                }
            }
            if($product->warrantlies()->count() > 0) {
                foreach($product->warrantlies as $warrantly) {
                    Translation::where([
                        'translatable_model' => ProductWarrantly::class,
                        'translatable_id'   => $warrantly->id,
                    ])->delete();
                }
            }
            Translation::where([
                'translatable_model' => Product::class,
                'translatable_id'   => $product->id,
            ])->delete();
            // End Remove Translations
            $product->versions()->delete();
            $product->productColors()->delete();
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
                $versionModel = $product->versions()->create([
                    'price' => $version['price'],
                ]);
                Translation::create([
                    'translatable_model' => ProductVersion::class,
                    'translatable_id'   => $versionModel->id,
                    'lang_id'           => $version['lang_id'],
                    'lang_key'          => 'name',
                    'lang_value'        => $version['name'],
                ]);
            }
        }

        if ($request->specifications) {
            $product->specifications()->sync($request->specifications);
        }

        if ($request->colors) {
            foreach ($request->colors as $color) {
                $colorModel = $product->productColors()->create([
                    'value' => $color['value'],
                ]);
                Translation::create([
                    'translatable_model' => ProductColor::class,
                    'translatable_id'   => $colorModel->id,
                    'lang_id'           => $color['lang_id'],
                    'lang_key'          => 'name',
                    'lang_value'        => $color['name'],
                ]);

            }
        }


        if ($request->addons) {
            foreach ($request->addons as $addon) {
                $addonModel = $product->addons()->create([
                    'price' => $addon['price'],
                ]);
                Translation::create([
                    'translatable_model' => ProductAddon::class,
                    'translatable_id'   => $addonModel->id,
                    'lang_id'           => $addon['lang_id'],
                    'lang_key'          => 'name',
                    'lang_value'        => $color['name'],
                ]);
            }
        }

        if ($request->warrantly) {
            foreach ($request->warrantly as $warrantly) {
                $warrantlyModel = $product->warrantlies()->create([
                    'type' => $warrantly['type'],
                ]);
                Translation::create([
                    'translatable_model' => ProductWarrantly::class,
                    'translatable_id'   => $warrantlyModel->id,
                    'lang_id'           => $warrantly['lang_id'],
                    'lang_key'          => 'title',
                    'lang_value'        => $warrantly['title'],
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
                        'lang_id'           => $translation['lang_id'],
                        'lang_key'               => $key,
                        'lang_value'             => $translation[$key],
                    ]);
                }
            }
        }


        return $this->sendRes($message, true);
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
        $product = Product::with('productColors', 'versions', 'addons', 'warrantlies')->where('uuid', $uuid)->first();

        if (!$product) {
            return $this->sendRes(translate('product not found'), false, [], [], 400);
        }

        // Add to recently viewed
        $product->recently_views()->sync(auth()->id());


        $product = new ProductShowResource($product);

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

        Translation::where([
            'translatable_model' => Product::class,
            'translatable_id'   => $product->id,
        ])->delete();
        // End Remove Translations
        $product->productColors()->delete();
        $product->addons()->delete();
        $product->warrantlies()->delete();
        $product->specifications()->detach(); // Detach old specifications
        $product->versions()->delete(); // Delete old versions
        $product->delete();
        return $this->sendRes(translate('product deleted successfully'), true);
    }
}
