<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';

    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }



    public function delivery_location() {
        return $this->belongsTo(Country::class, 'delivery_location_id');
    }

    public function shipping_method() {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function payment_method() {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function payment() {
        return $this->hasOne(Payment::class, 'order_id');
    }

    public function delivery_information() {
        return $this->hasOne(OrderDeliveryInformation::class, 'order_id');
    }



    public function items() {
        return $this->hasMany(OrderItem::class, 'order_id');
    }


    public function payments() {
        return $this->hasMany(Payment::class, 'order_id');
    }


    public function status() {
        return $this->belongsTo(Status::class, 'status_id');
    }


    public function status_history() {
        return $this->belongsToMany(Status::class, 'orders_status_history', 'order_id');
    }

}
