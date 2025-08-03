<?php

namespace App\Models;

use App\Traits\TranslateTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductColor extends Model
{
    use HasFactory, SoftDeletes, TranslateTrait;

    protected $table = 'products_colors';

    protected $guarded = [];


    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }



    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }


}
