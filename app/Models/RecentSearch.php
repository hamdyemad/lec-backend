<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecentSearch extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'recent_searches';

    protected $guarded = [];


    public function user() {
        return $this->belongsTo(User::class, 'seller_id');
    }

}
