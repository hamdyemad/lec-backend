<?php

use App\Models\Currency;
use App\Models\Language;
use App\Models\Permession;
use App\Models\Setting;
use App\Models\Translation;
use Illuminate\Support\Facades\Route;

function activeRoute($routeName) {
    if(Route::current()->getName() == $routeName) {
        return true;
    }
}

// function price($price) {
//     $currency = Currency::where('default', 1)->first();
//     return $currency->symbol . $price;
// }

// function currency() {
//     $currency = Currency::where('default', 1)->first();
//     return $currency->symbol;
// }

function translate($key) {
    $language = Language::where('code', app()->getLocale())->first();
    if($language) {
        $translation = Translation::where(['lang_key' =>  $key, 'lang_id' => $language->id])->first();
        if($translation) {
            return $translation->lang_value;
        } else {
            $translation = Translation::create([
                'lang_id' => $language->id,
                'lang_key' => $key,
                'lang_value' => $key
            ]);
            return $translation->lang_value;
        }
    } else {
        return $key;
    }
}

function get_setting($type) {
    $setting = Setting::where('type', $type)->first();
    if($setting) {
        return $setting->value;
    } else {
        return null;
    }
}

function permession_maker($name, $key, $groupBy) {
    $permession = Permession::where('key', $key)
    ->where('name', $name)
    ->first();
    if($permession) {
        return null;
    } else {
        Permession::create([
            'name' => $name,
            'key' => $key,
            'group_by' => $groupBy
        ]);
    }
}
