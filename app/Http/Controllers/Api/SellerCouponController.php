<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
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

class SellerCouponController extends Controller
{
    use Res, FileUploads;

    public $types = [
        'number' => 1,
        'percentage' => 2,
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $feature_type = FeatureType::where('name', 'coupon')->first();
        $coupons = $feature_type->features()
        ->select(['id', 'user','feature','title', 'type', 'number'])
        ->where('user', $user->id)
        ->get();
        return $this->sendRes('all coupons', true, $coupons);
    }


    public function store(Request $request)
    {
        $values = array_values($this->types);
        $user = auth()->user();
        $rules = [
            'title' => ['required', 'string', 'max:255', 'unique:tb_feature,title'],
            'type' => ['required', 'numeric', Rule::in($values)],
            'number' => ['required', 'numeric'],
        ];

        $messages = [
            'type.in' => 'type should be in:' . implode(',', $this->types)
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if($validator->fails()) {
            $message = implode('<br>', $validator->errors()->all());
            return $this->sendRes($message, false, [], $validator->errors(), 400);
        }


        $feature_type = FeatureType::where('name', 'coupon')->first();


        $data = [
            'feature' => $feature_type->id,
            'title' => $request->title,
            'type' => $request->type,
            'number' => $request->number
        ];

        $coupon = $user->features()->create($data);

        return $this->sendRes(__('coupons.add success'), true, $coupon);
    }

    public function edit(Request $request, $id)
    {
        $user = auth()->user();
        $values = array_values($this->types);
        $coupon = $user->features()->select(['id', 'user','feature','title', 'type', 'number'])->find($id);
        if($coupon) {
            $rules = [
                'title' => ['required', 'string', 'max:255', Rule::unique('tb_feature','title')->ignore($id) ],
                'type' => ['required', 'numeric', Rule::in($values)],
                'number' => ['required', 'numeric'],
            ];

            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                $message = implode('<br>', $validator->errors()->all());
                return $this->sendRes($message, false, [], $validator->errors(), 400);
            }
            $data = [
                'title' => $request->title,
                'type' => $request->type,
                'number' => $request->number
            ];
            $user->features()->update($data);
            return $this->sendRes(__('coupons.edit success'), true, $coupon);
        } else {
            return $this->sendRes(__('coupons.not found'), false, [], [], 400);
        }
    }

    public function show(Request $request, $id) {
        $user = auth()->user();
        $coupon = $user->features()->select(['id', 'user','feature','title', 'type', 'number'])->find($id);
        if($coupon) {
            return $this->sendRes(__('coupons.success'), true, $coupon);
        } else {
            return $this->sendRes(__('coupons.not found'), false, [], [], 400);
        }
    }


    public function delete(Request $request, $id)
    {
        $user = auth()->user();
        $coupon = $user->features()->select(['id', 'user','feature','title', 'type', 'number'])->find($id);
        if($coupon) {
            $coupon->delete();
            return $this->sendRes(__('coupons.delete success'), true);
        } else {
            return $this->sendRes(__('coupons.not found'), false, [], [], 400);
        }
    }



}
