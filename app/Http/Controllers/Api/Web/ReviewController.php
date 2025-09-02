<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\CategoryResource;
use App\Http\Resources\Web\ReviewResource;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\Feature;
use App\Models\FeatureType;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    use Res;

    public function index(Request $request)
    {
        $authUser = auth()->user();

        $validator = Validator::make($request->all(), [
            'per_page' => ['nullable', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }
        $per_page = $request->per_page ?? 10;

        $reviews = Review::with('user')->latest();
        $reviews = $reviews->paginate($per_page);

        ReviewResource::collection($reviews);

        return $this->sendRes('', true, $reviews);
    }



    public function store(Request $request, $product_uuid)
    {

        $product = Product::where('uuid', $product_uuid)->first();
        if(!$product) {
            return $this->sendRes(__('main.not found'), false, [], [], 404);
        }
        $rules = [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }
        $product->reviews()->create([
            'user_id' => auth()->id(),
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]);
        return $this->sendRes(__('reviews.review added success'), true);

    }



}
