<?php

use App\Models\Currency;
use App\Models\Language;
use App\Models\Permession;
use App\Models\Setting;
use App\Models\Translation;
use Illuminate\Support\Facades\Route;



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
