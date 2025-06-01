<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $table = 'carts';

    protected $guarded = [];


    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function seller() {
        return $this->belongsTo(Product::class, 'seller_id');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
