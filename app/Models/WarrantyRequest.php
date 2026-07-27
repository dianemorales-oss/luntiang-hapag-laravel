<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WarrantyRequest extends Model
{
    protected $fillable = ['user_id','product_name','order_number','purchase_date','quality_issue','defect_description','proof_of_purchase_path','damage_photo_path','status','admin_note'];
    public function user(){ return $this->belongsTo(User::class); }
}
