<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClaimedCoupon extends Model
{
    protected $fillable = ['user_id','promotion_id','claimed_at','used_at','expires_at'];
    protected $casts = ['claimed_at'=>'datetime','used_at'=>'datetime','expires_at'=>'datetime'];
    public function user(){ return $this->belongsTo(User::class); }
    public function promotion(){ return $this->belongsTo(Promotion::class); }

    public function isExpired(): bool
    {
        if ($this->used_at) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return true;
        return false;
    }
}

