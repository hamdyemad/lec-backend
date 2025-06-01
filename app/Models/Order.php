<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';

    protected $guarded = [];



    public static function generateReference()
    {
        $lastOrder = self::latest('id')->first();
        $nextNumber = $lastOrder ? intval(substr($lastOrder->reference, -4)) + 1 : 1;
        return "SP_" . str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
    }




    public function user() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function client() {
        return $this->belongsTo(User::class, 'client_id')->where('type', 1);
    }

    public function driver() {
        return $this->belongsTo(User::class, 'driver_id')->where('type', 3);
    }

    public function client_location() {
        return $this->belongsTo(UserLocation::class, 'client_location_id');
    }

    public function items() {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function invoice() {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    public function payments() {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function chat() {
        return $this->hasMany(Message::class, 'order_id');
    }

    public function status() {
        return $this->belongsTo(Status::class, 'status_id');
    }
    public function pay_status() {
        return $this->belongsTo(Status::class, 'pay_status_id');
    }

    public function pickups() {
        return $this->hasMany(OrderPickup::class, 'order_id');
    }


}
