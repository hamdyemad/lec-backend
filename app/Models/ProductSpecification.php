<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVersion extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'products_versions';

    protected $guarded = [];


    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }


}
