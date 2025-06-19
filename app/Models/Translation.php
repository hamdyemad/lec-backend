<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $table = 'translations';
    protected $guarded = [];

    public function language() {
        return $this->belongsTo(Language::class, 'lang_id');
    }




}
