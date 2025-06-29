<?php

namespace App\Models;
use App\Traits\TranslateTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductWarrantly extends Model
{
    use HasFactory, SoftDeletes, TranslateTrait;
    protected $table = 'products_warrantly';

    protected $guarded = [];


    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
