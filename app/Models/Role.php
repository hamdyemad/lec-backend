<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $table = 'roles';

    protected $guarded = [];


    public function permessions() {
        return $this->belongsToMany(Permession::class, 'roles_permessions', 'role_id')->withTimestamps();
    }

    public function users() {
        return $this->belongsToMany(User::class, 'users_roles', 'role_id')->withTimestamps();
    }
}
