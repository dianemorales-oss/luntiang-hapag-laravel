<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model
{
    protected $fillable = ['user_id','order_number','status','subtotal','delivery_fee','discount','total','delivery_method','payment_method','promo_code','delivery_address','delivery_city','delivery_province','delivery_zip','delivery_notes','gift_note','preferred_delivery_time','is_free_delivery','estimated_harvest_time','customer_name','customer_email','customer_phone','cancellation_reason','cancellation_notes','cancelled_at'];
    protected $casts = ['subtotal'=>'decimal:2','delivery_fee'=>'decimal:2','discount'=>'decimal:2','total'=>'decimal:2','is_free_delivery'=>'boolean','cancelled_at'=>'datetime'];
    public function user(){ return $this->belongsTo(User::class); }
    public function items(){ return $this->hasMany(OrderItem::class); }
}
