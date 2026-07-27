<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Promotion extends Model
{
    protected $fillable = ['code','description','discount_type','discount_value','min_order','max_uses','used_count','is_active','is_free_delivery','expires_at','claimed_validity_days'];
    protected $casts = ['discount_value'=>'decimal:2','min_order'=>'decimal:2','is_active'=>'boolean','is_free_delivery'=>'boolean','expires_at'=>'date','claimed_validity_days'=>'integer'];
    public $timestamps = true;

    public function claimedCoupons()
    {
        return $this->hasMany(ClaimedCoupon::class);
    }
}
