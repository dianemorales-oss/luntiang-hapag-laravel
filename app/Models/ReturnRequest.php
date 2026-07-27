<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReturnRequest extends Model
{
    protected $fillable = ['user_id','order_number','product_name','purchase_date','reason_category','reason','product_condition','proof_of_purchase_path','damage_photo_path','status','admin_note'];
    public function user(){ return $this->belongsTo(User::class); }
}
