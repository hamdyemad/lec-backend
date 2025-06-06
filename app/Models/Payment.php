<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';

    protected $guarded = [];


    public function user() {
        return $this->belongsTo(User::class, 'paid_user_id');
    }

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }


}
