<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Models\Country;
use App\Models\ShippingMethod;
use App\Models\Translation;
use App\Traits\Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    use Res;

    public function index(Request $request)
    {
        $cities = City::with('country')->latest();
        $per_page = $request->get('per_page', 12);
        $country_id = $request->get('country_id', '');
        $keyword = $request->get('keyword');


        if($keyword) {
            $cities = $cities->whereHas('translationRelation', function($query) use($keyword) {
                $query->where('lang_value', 'like', "%$keyword%");
            })->orWhereHas('country', function($query) use($keyword) {
                $query
                ->where('name_en', 'like', "%$keyword%")
                ->orWhere('name_ar', 'like', "%$keyword%");
            });
        }

        if($country_id) {
            $cities = $cities->where('country_id', $country_id);
        }

        $cities = $cities->paginate($per_page);

        $cities->getCollection()->transform(function($city) {
            return new CityResource($city);
        });
        return $this->sendRes('all cities', true, $cities);
    }
}
