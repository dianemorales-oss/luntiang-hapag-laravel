<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LiveChatMessage extends Model
{
    protected $fillable = ['chat_key','user_id','customer_name','sender','message','image_path'];

    // Keep created_at support, but do not require updated_at.
    // Older database copies from the original PHP app only had created_at,
    // so writing updated_at caused Live Chat buttons/pages to throw 500 errors.
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    public function user(){ return $this->belongsTo(User::class); }
}
