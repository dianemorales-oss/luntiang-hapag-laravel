<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerAddress extends Model
{
    protected $fillable = ['user_id','label','address','city','province','zip','is_default'];
    protected $casts = ['is_default'=>'boolean'];
    public function user(){ return $this->belongsTo(User::class); }
}
