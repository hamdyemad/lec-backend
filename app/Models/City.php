<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use HasFactory, TranslateTrait, SoftDeletes;
    protected $table = 'cities';

    protected $guarded = [];


    public function country() {
        return $this->belongsTo(Country::class);
    }


    public function translationRelation() {
        return $this->hasMany(Translation::class, 'translatable_id')->where('translatable_model', self::class);
    }


}
