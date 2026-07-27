<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    protected $fillable = ['category_id','name','slug','variety','description','price','unit','image','image_2','image_3','calories','protein','fiber','vitamin_a','vitamin_c','best_for','storage_instructions','shelf_life','harvest_time','plants_available','is_best_seller','is_new','is_active','is_featured'];
    protected $casts = ['price'=>'decimal:2','is_best_seller'=>'boolean','is_new'=>'boolean','is_active'=>'boolean','is_featured'=>'boolean'];
    public function category(){ return $this->belongsTo(Category::class); }
    public function reviews(){ return $this->hasMany(Review::class); }
    public function orderItems(){ return $this->hasMany(OrderItem::class); }
}
