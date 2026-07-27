<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LiveChatMessage extends Model
{
    protected $fillable = ['chat_key','user_id','customer_name','sender','message','image_path'];
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    public function user(){ return $this->belongsTo(User::class); }
}
