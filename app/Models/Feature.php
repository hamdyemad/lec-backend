<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;
    protected $table = 'tb_feature';

    protected $guarded = [];


    public function feature_type() {
        return $this->belongsTo(Feature::class, 'feature');
    }
}
