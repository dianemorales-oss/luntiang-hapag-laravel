<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Review extends Model
{
    protected $fillable = ['user_id','product_id','order_id','rating','review_title','freshness_rating','packaging_rating','delivery_rating','comment','photos','is_verified','is_approved','helpful_count','admin_reply','admin_replied_at'];
    protected $casts = ['is_verified'=>'boolean','is_approved'=>'boolean','admin_replied_at'=>'datetime'];
    public function user(){ return $this->belongsTo(User::class); }
    public function product(){ return $this->belongsTo(Product::class); }
    public function order(){ return $this->belongsTo(Order::class); }
}
