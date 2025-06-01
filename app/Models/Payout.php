<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;
    protected $table = 'payouts';

    protected $guarded = [];


    public function withdrawl() {
        return $this->belongsTo(WalletWithdrawl::class, 'withdrawl_id');
    }


}
