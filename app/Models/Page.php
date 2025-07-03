<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, TranslateTrait, SoftDeletes;
    protected $table = 'pages';

    protected $guarded = [];

    public function translationRelation() {
        return $this->hasMany(Translation::class, 'translatable_id')->where('translatable_model', self::class);
    }

}
