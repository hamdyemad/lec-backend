<?php

namespace App\Traits;

use App\Models\Language;
use App\Models\Translation;

trait TranslateTrait
{

    public function translate($key)
    {
        $language = Language::where('code', app()->getLocale())->first();
        if ($language) {
            $translation = Translation::where([
                'lang_key' =>  $key,
                'lang_id' => $language->id,
                'translatable_model' => self::class,
                'translatable_id' => $this->id,
            ])->first();
            if ($translation) {
                return $translation->lang_value;
            } else {
                return '';
            }
        } else {
            return $key;
        }
    }


    public function translations($key) {
        return Language::select('id', 'name')->with(['translations' => function ($q) use ($key) {
            $q->where('lang_key', $key)->where('translatable_model', self::class);
        }])->orderBy('id')->get()->map(function ($lang) use ($key) {
            $value = optional($lang->translations->firstWhere('translatable_id', $this->id))->lang_value ?? '';
            return [
                'id' => $lang->id,
                'name' => $lang->name,
                'value' => $value
            ];
        });
    }
}
