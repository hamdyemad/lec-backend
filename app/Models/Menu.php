<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    protected $table = 'rc_menu';

    protected $guarded = [];



    public function permession() {
        return $this->belongsTo(Permession::class, 'permession_id');
    }

}
