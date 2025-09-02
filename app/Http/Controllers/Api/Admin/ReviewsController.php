<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\Translate;
use App\Http\Resources\Web\ReviewResource;
use App\Models\Language;
use App\Models\Message;
use App\Models\Review;
use App\Models\Translation;
use App\Traits\Res;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReviewsController extends Controller
{
    use Res;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = request('per_page') ?? 12;
        $client_id = request('client_id') ?? null;
        $product_id = request('product_id') ?? null;
        $reviews = Review::with('product','user')->orderBy('created_at', 'desc');

        if($client_id) {
            $reviews = $reviews->where('user_id', $client_id);
        }

        if($product_id) {
            $reviews = $reviews->where('product_id', $product_id);
        }

        $reviews = $reviews->paginate($per_page);
        ReviewResource::collection($reviews);


        return $this->sendRes('', true, $reviews);
    }

}
