<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permession extends Model
{
    use HasFactory;
    protected $table = 'permessions';

    protected $guarded = [];


    public function roles() {
        return $this->belongsToMany(Permession::class, 'roles_permessions', 'permession_id')->withTimestamps();
    }

}
