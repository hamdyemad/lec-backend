<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureType extends Model
{
    use HasFactory;
    protected $table = 'tb_feature_type';

    protected $guarded = [];


    public function features() {
        return $this->hasMany(Feature::class, 'feature');
    }
}
