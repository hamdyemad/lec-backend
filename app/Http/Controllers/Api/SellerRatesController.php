<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Rate;
use App\Models\SellerRate;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SellerRatesController extends Controller
{
    use Res, FileUploads;

    public $seller_type = 2;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $paginate = $request->paginate;
        $rates = $user->rates()->latest();

        $rules = [
            'paginate' => ['nullable', 'integer'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }



        if($request->rate) {
            $rates = $rates->where('rate', 'like', "%$request->rate%");
        }

        if($request->description) {
            $rates = $rates->where('description', 'like', "%$request->description%");
        }


        if($paginate) {
            $rates = $rates->paginate($paginate);
        } else {
            $rates = $rates->get();
        }

        return $this->sendRes(__('rates.index'), true, $rates);

    }



    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'rate' => ['required', 'array'],
            'rate.*' => ['required', 'string','in:0,1,2,3,4,5'],
            'seller_id' => ['required', 'array'],
            'seller_id.*' => ['required', 'exists:rc_users,id', function($attr, $val, $fail) {
                $user = User::where('type', $this->seller_type)->where('id', $val)->first();
                if(!$user) {
                    $fail(__('auth.user not found'));
                }
            }],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'order_id.*' => ['required', 'exists:orders,id'],
            'order_id' => ['required', 'array'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        if(is_array($request->rate)) {
            foreach($request->rate as $key => $rate) {
                $rate = $user->rates()->create([
                    'to_user_id' => $request->seller_id[$key],
                    'rate' => $request->rate[$key],
                    'order_id' => $request->order_id[$key],
                    'description' => $request->description[$key],
                ]);
                $rate->order->update([
                    'client_rating' => true,
                ]);
            }
        }

        return $this->sendRes(__('validation.success'), true, $rate);
    }

    public function edit(Request $request, $rate_id)
    {
        $user = auth()->user();
        $rate = $user->rates()->where(['id' => $rate_id])->first();
        if($rate) {
            $rules = [
                'rate' => ['required','integer','in:0,1,2,3,4,5'],
                'description' => ['required', 'string'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                $message = implode('<br>', $validator->errors()->all());
                return $this->sendRes($message, false, [], $validator->errors(), 400);
            }

            $rate->update([
                'rate' => $request->rate,
                'description' => $request->description,
            ]);
            return $this->sendRes(__('rates.update'), true, $rate);
        } else {
            return $this->sendRes(__('main.not found'), false, [], [], 400);
        }

    }
    public function show(Request $request, $rate_id)
    {
        $user = auth()->user();
        $rate = $user->rates()->where(['id' => $rate_id])->first();
        if($rate) {
            return $this->sendRes(__('rates.show'), true, $rate);
        } else {
            return $this->sendRes(__('main.not found'), false, [], [], 400);
        }
    }


    public function delete(Request $request, $rate_id)
    {
        $user = auth()->user();
        $rate = $user->rates()->where(['id' => $rate_id])->first();
        if($rate) {
            $rate->delete();
            return $this->sendRes(__('rates.delete'), true, $rate);
        } else {
            return $this->sendRes(__('main.not found'), false, [], [], 400);
        }
    }


    // BI

    public function bi_rating(Request $request)
    {
        $paginate = $request->paginate;
        $seller_id = $request->seller_id;
        $user = auth()->user();
        $rules = [
            'paginate' => ['nullable', 'integer'],
            'seller_id' => ['required', 'exists:rc_users,id', function($attr, $val, $fail) {
                $user = User::where('type', $this->seller_type)->where('id', $val)->first();
                if(!$user) {
                    $fail(__('auth.user not found'));
                }
            }],
        ];

        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }

        $seller = User::find($request->seller_id);

        $where = ['to_user_id' => $seller->id];
        $select = ['id', 'from_user_id', 'to_user_id','rate', 'description', 'created_at', 'from_user_id as user_id', 'to_user_id as seller_id'];
        $rates = Rate::select($select)->where($where)->with('user:id,uuid,type,username,image')->latest();

        if($paginate) {
            $rates = $rates->paginate($paginate);
        } else {
            $rates = $rates->get();
        }

        // Get the total number of ratings for a product
        $totalRatings = Rate::where($where)->count();
        $sumRates = Rate::where($where)->sum('rate');


        // Calculate the final average rating
        if ($totalRatings > 0) {
            $averageRating = $sumRates / $totalRatings;
        } else {
            $averageRating = 0;  // No ratings yet
        }

        // Convert the average rating to a percentage (1 to 5 scale)
        $averagePercentage = ($averageRating / 5) * 100;
        if($averagePercentage > 100) {
            $averagePercentage = 100;
        }

        $averagePercentage = round($averagePercentage, 2);

        // Calculate the percentage of each star rating (1 to 5)
        $starPercentages = [];

        // return $starPercentages;
        for ($i = 1; $i <= 5; $i++) {
            $starCount = Rate::where($where)->where('rate', "$i")->count();
            if($totalRatings > 0) {
                $percentage = ($starCount / $totalRatings) * 100;
            } else {
                $percentage = 0;
            }
            $starPercentages[$i] = round($percentage, 2);
        }


        $data = [
            'starPercentages' => $starPercentages,
            'allStarPercentages' => $averagePercentage,
            'rates' => $rates,
        ];

        return $this->sendRes(__('rates.index'), true, $data);

    }


}
