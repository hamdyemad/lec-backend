<?php

namespace App\Models;
use App\Traits\TranslateTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportPage extends Model
{
    use HasFactory, TranslateTrait;
    protected $table = 'support_pages';

    protected $guarded = [];


    public function translationsRelations() {
        return $this->hasMany(Translation::class, 'translatable_id')->where('translatable_model', self::class);
    }


}
