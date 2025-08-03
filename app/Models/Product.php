<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, TranslateTrait;
    protected $table = 'products';

    protected $guarded = [];

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }


    public function translationsRelations() {
        return $this->hasMany(Translation::class, 'translatable_id')->where('translatable_model', self::class);
    }
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

    public function productColors() {
        return $this->hasMany(ProductColor::class, 'product_id');
    }



    public function addons() {
        return $this->hasMany(ProductAddon::class, 'product_id');
    }





}
