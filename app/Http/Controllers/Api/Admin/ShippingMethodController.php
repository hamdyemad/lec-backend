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
use App\Models\ShippingMethod;
use App\Models\Translation;
use App\Models\User;
use App\Models\UserType;
use App\Traits\FileUploads;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShippingMethodController extends Controller
{
    use Res;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function index(Request $request)
    {
        $per_page = $request->per_page ?? 10;
        $shipping_methods = ShippingMethod::latest()->paginate($per_page);
        return $this->sendRes('', true, $shipping_methods);
    }


    public function form(Request $request, $shippingMethod = null)
    {

        if($shippingMethod) {
            $rules = [
                'name' => ['nullable', 'string', 'max:255'],
                'value' => ['nullable', 'integer'],
                'type' => ['nullable', 'in:number,percent']
            ];
        } else {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'value' => ['required', 'integer'],
                'type' => ['required', 'in:number,percent']
            ];
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }

        $data = [];

        ($request->name) ? $data['name'] = $request->name : null;
        ($request->value) ? $data['value'] = $request->value : null;
        ($request->type) ? $data['type'] = $request->type : null;

        if ($shippingMethod) {
            $message = __('main.updated success');
            $shippingMethod->update($data);
        } else {
            $message = __('main.created success');
            $shippingMethod = ShippingMethod::create($data);
        }

        return $this->sendRes($message, true);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $id)
    {
        $shippingMethod = ShippingMethod::find($id);
        if (!$shippingMethod) {
            return $this->sendRes(__('main.not found'), false, [], [], 400);
        }
        return $this->form($request, $shippingMethod);
    }


    public function show(Request $request, $id)
    {
        $shippingMethod = ShippingMethod::find($id);

        if (!$shippingMethod) {
            return $this->sendRes(__('main.not found'), false, [], [], 400);
        }

        return $this->sendRes('', true, $shippingMethod);
    }

    public function delete(Request $request, $id)
    {
        $shippingMethod = ShippingMethod::find($id);
        if (!$shippingMethod) {
            return $this->sendRes(__('main.not found'), false, [], [], 400);
        }
        $shippingMethod->delete();
        return $this->sendRes(__('main.deleted success'), true);

    }
}
