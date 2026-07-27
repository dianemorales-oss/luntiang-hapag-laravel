<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Category extends Model
{
    protected $fillable = ['name','slug','description','image','is_active','sort_order'];
    protected $casts = ['is_active'=>'boolean'];
    public function products(){ return $this->hasMany(Product::class); }
}
