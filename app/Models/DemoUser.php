<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoUser extends Model
{
    use HasFactory;
    protected $table = 'demo_users';

    protected $guarded = [];


}
