<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'password',
        'reset_token',
        'reset_token_expires',
    ];

    protected $hidden = [
        'password',
        'reset_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'reset_token_expires' => 'datetime',
        ];
    }

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function claimedCoupons()
    {
        return $this->hasMany(ClaimedCoupon::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
