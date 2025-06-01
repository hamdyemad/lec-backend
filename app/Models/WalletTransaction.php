<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;
    protected $table = 'wallet_transactions';

    protected $guarded = [];

    public function account() {
        return $this->belongsTo(Account::class, 'account_id');
    }

}
