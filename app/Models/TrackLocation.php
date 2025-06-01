<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackLocation extends Model
{
    use HasFactory;

    protected $table = 'tracking_locations';

    protected $guarded = [];


    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
