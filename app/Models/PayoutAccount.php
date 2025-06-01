<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutAccount extends Model
{
    use HasFactory;
    protected $table = 'payouts_accounts';

    protected $guarded = [];


    public function driver() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payment_method() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
