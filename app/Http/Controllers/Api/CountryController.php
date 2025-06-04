<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\ShippingMethod;
use App\Traits\Res;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    use Res;

    public function index(Request $request)
    {
        $select = [
            '*'
        ];
        $keyword = $request->keyword;

        $countries = Country::select($select);

        if ($keyword) {
            $countries = $countries
            ->where('name_en', 'like', '%' . $keyword . '%')
            ->orWhere('code', 'like', '%' . $keyword . '%')
            ->orWhere('call_key', 'like', '%' . $keyword . '%');
        }

        $countries = $countries->get()->map(function ($country) {
            $country->flag = asset('/' . $country->flag);
            return $country;
        });
        return $this->sendRes(translate('countries data'), true, $countries, [], 200);
    }


    public function shipping_methods(Request $request)
    {
        $shipping_methods = ShippingMethod::latest()->get();
        return $this->sendRes(translate('shipping methods data'), true, $shipping_methods, [], 200);
    }

}
