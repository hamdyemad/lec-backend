<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPickup extends Model
{
    use HasFactory;
    protected $table = 'orders_pickups';

    protected $guarded = [];


    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function pickup_items() {
        return $this->hasMany(OrderItem::class, 'order_pickup_id');
    }

    public function seller() {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function seller_location() {
        return $this->belongsTo(UserLocation::class, 'seller_location_id');
    }

    public function status() {
        return $this->belongsTo(Status::class, 'status_id');
    }


}
