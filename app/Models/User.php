<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;


    protected $table = 'rc_users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
        // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */

    public function type() {
        return $this->belongsTo(UserType::class, 'type');
    }




    public function locations() {
        return $this->hasMany(UserLocation::class, 'user_id');
    }

    public function products() {
        return $this->hasMany(Product::class, 'seller_id');
    }



    public function recent_searches() {
        return $this->hasMany(RecentSearch::class, 'user_id');
    }

    public function favorite_products() {
        return $this->hasMany(FavoriteProduct::class, 'user_id');
    }



    public function roles() {
        return $this->belongsToMany(Role::class, 'users_roles', 'user_id')->withTimestamps();
    }

    public function accounts() {
        return $this->hasMany(Account::class, 'user_id');
    }


}
