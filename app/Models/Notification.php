<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model
{
    protected $fillable = ['type','related_id','title','message','customer_name','related_link','is_read'];
    protected $casts = ['is_read'=>'boolean'];
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
