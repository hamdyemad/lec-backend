<?php

namespace App\Models;
use App\Traits\TranslateTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes, TranslateTrait;
    protected $table = 'categories';

    protected $guarded = [];


    public function translationsRelations() {
        return $this->hasMany(Translation::class, 'translatable_id')->where('translatable_model', self::class);
    }



}
