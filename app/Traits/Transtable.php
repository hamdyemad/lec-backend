<?php

namespace App\Traits;

trait Translatable
{
    /**
     * Morph relation with custom column names: translatable_model + translatable_id
     */
    public function translations()
    {
        return $this->morphMany(\App\Models\Translation::class, 'translatable', 'translatable_model', 'translatable_id');
    }

    /**
     * Get translation value by key and lang_id
     *
     * @param string $key
     * @param int|null $langId
     * @return string
     */
    public function translate(string $key, ?int $langId): string
    {
        return $this->translations()
            ->where('lang_id', $langId)
            ->where('lang_key', $key)
            ->value('lang_value') ?? '';
    }

    /**
     * Get full translation record object
     *
     * @param string $key
     * @param int|null $langId
     * @return \App\Models\Translation|null
     */
    public function translationObject(string $key, ?int $langId)
    {
        return $this->translations()
            ->where('lang_id', $langId)
            ->where('lang_key', $key)
            ->first();
    }
}
