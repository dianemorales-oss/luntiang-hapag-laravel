<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Promotion extends Model
{
    protected $fillable = ['code','description','discount_type','discount_value','min_order','max_uses','used_count','is_active','is_free_delivery','expires_at'];
    protected $casts = ['discount_value'=>'decimal:2','min_order'=>'decimal:2','is_active'=>'boolean','is_free_delivery'=>'boolean','expires_at'=>'date'];
    public $timestamps = true;
}
