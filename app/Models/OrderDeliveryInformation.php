<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDeliveryInformation extends Model
{
    use HasFactory;
    protected $table = 'orders_delivery_information';

    protected $guarded = [];

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }

}
