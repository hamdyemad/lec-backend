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



    public function driver() {
        return $this->hasOne(Driver::class, 'user_id');
    }

    public function locations() {
        return $this->hasMany(UserLocation::class, 'user_id');
    }

    public function products() {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function features() {
        return $this->hasMany(Feature::class, 'user');
    }


    // Client
    public function orders() {
        return $this->hasMany(Order::class, 'client_id');
    }

    // Driver
    public function driver_orders() {
        return $this->hasMany(Order::class, 'driver_id');
    }

    // Seller
    public function pickups() {
        return $this->hasMany(OrderPickup::class, 'seller_id');
    }

    public function carts() {
        return $this->hasMany(Cart::class, 'client_id');
    }

    public function withdrawls() {
        return $this->hasMany(WalletWithdrawl::class, 'user_id');
    }

    public function iban() {
        return $this->hasOne(Iban::class, 'user_id');
    }

    public function transactions() {
        return $this->hasMany(WalletTransaction::class, 'user_id');
    }


    public function rates() {
        return $this->hasMany(Rate::class, 'from_user_id');
    }

    public function seller_rates() {
        return $this->hasMany(Rate::class, 'to_user_id');
    }

    public function favorite_products() {
        return $this->hasMany(FavoriteProduct::class, 'user_id');
    }


    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function track_location()
    {
        return $this->hasMany(TrackLocation::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Feature::class, 'category_id')->select([
            'id',
            'feature',
            'title',
            'image',
            'created_at',
            'updated_at'
        ]);
    }


    public function roles() {
        return $this->belongsToMany(Role::class, 'users_roles', 'user_id')->withTimestamps();
    }

    public function accounts() {
        return $this->hasMany(Account::class, 'user_id');
    }


}
