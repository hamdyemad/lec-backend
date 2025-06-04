<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCard extends Model
{
    use HasFactory;
    protected $table = 'users_cards';

    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class, 'client_id');
    }


}
