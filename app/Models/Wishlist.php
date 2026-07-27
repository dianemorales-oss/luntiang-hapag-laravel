<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Wishlist extends Model
{
    protected $table = 'wishlist';
    protected $fillable = ['user_id','product_id'];
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    public function user(){ return $this->belongsTo(User::class); }
    public function product(){ return $this->belongsTo(Product::class); }
}
