<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'products';

    protected $guarded = [];


    public function user() {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category() {
        return $this->belongsTo(Feature::class, 'category_id');
    }

    public function versions() {
        return $this->hasMany(ProductVersion::class, 'product_id');
    }

    public function warrantlies() {
        return $this->hasMany(ProductWarrantly::class, 'product_id');
    }

    public function specifications() {
        return $this->belongsToMany(Specification::class, 'products_specifications', 'product_id', 'specification_id');
    }



    public function recently_views() {
        return $this->belongsToMany(User::class, 'recently_views', 'product_id', 'user_id');
    }

    public function colors() {
        return $this->hasMany(ProductColor::class, 'product_id');
    }

    public function addons() {
        return $this->hasMany(ProductAddon::class, 'product_id');
    }

    public function translations()
    {
        return $this->hasMany(Translation::class, 'translatable_id')->where('translatable_model', self::class);
    }

    public function translate($key)
    {
        $language = Language::where('code', app()->getLocale())->first();
        if($language) {
            $translation = Translation::where([
                'lang_key' =>  $key,
                'lang_id' => $language->id,
                'translatable_model' => self::class,
                'translatable_id' => $this->id,
                ])->first();
            if($translation) {
                return $translation->lang_value;
            } else {
                return '';
            }
        } else {
            return $key;
        }

    }



}
