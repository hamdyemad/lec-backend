<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletWithdrawl extends Model
{
    use HasFactory;
    protected $table = 'wallet_withdrawal';

    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function iban() {
        return $this->belongsTo(Iban::class);
    }

    public function payouts() {
        return $this->hasMany(Payout::class, 'withdrawl_id');
    }

    public function account() {
        return $this->belongsTo(Account::class, 'user_account_id')->withTrashed();
    }

    public function payment_method() {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }



}
