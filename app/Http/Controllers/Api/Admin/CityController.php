<?php

namespace App\Http\Controllers\Api\Admin;

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
        $keyword = $request->get('keyword');
        $country_id = $request->get('country_id');


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




    public function form(Request $request, $city = null)
    {
        $rules = [
            'country_id' => ['required', 'string', 'exists:countries,id'],
            'lang_id' => ['required', 'array'],
            'lang_id.*' => ['required', 'exists:languages,id'],
            'name' => ['required', 'array'],
            'name.*' => ['required', 'string', 'max:255'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->errorResponse($validator);
        }


        $names = $request->name;
        $lang_ids = $request->lang_id;

        $data = [
            'country_id' => $request->country_id,
        ];

        if($city) {
            Translation::where([
                'translatable_model' => City::class,
                'translatable_id'   => $city->id,
            ])->delete();
            $city->update($data);
            $message = translate('city updated success');
        } else {
            $data['uuid'] = \Str::uuid();
            $city = City::create($data);
            $message = translate('city created success');
        }

        // Translations
        if($lang_ids) {
            foreach($lang_ids as  $i => $val) {
                foreach (['name'] as $key) {
                    Translation::create([
                        'translatable_model' => City::class,
                        'translatable_id'   => $city->id,
                        'lang_id'           => $lang_ids[$i],
                        'lang_key'               => $key,
                        'lang_value'             => $names[$i],
                    ]);
                }
            }
        }

        return $this->sendRes($message, true, [], [], 200);
    }

    public function store(Request $request)
    {
        return $this->form($request);
    }

    public function edit(Request $request, $uuid)
    {
        $city = City::where('uuid', $uuid)->first();
        if(!$city) {
            return $this->sendRes(translate('city not found'), false, [], [], 400);
        }
        return $this->form($request, $city);
    }

    public function show(Request $request, $uuid) {
        $city = City::with('country')->where('uuid', $uuid)->first();
        if(!$city) {
            return $this->sendRes(translate('city not found'), false, [], [], 400);
        }
        $city->name = $city->translations('name');
        return $this->sendRes(translate('city found'), true, $city);
    }

    public function delete(Request $request, $uuid) {
        $city = City::where('uuid', $uuid)->first();
        if(!$city) {
            return $this->sendRes(translate('city not found'), false, [], [], 400);
        }
        Translation::where([
            'translatable_model' => City::class,
            'translatable_id'   => $city->id,
        ])->delete();
        $city->delete();
        return $this->sendRes(translate('city deleted successfully'), true, [], [], 200);
    }

}
