<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChatBotState extends Model
{
    protected $table = 'chat_bot_state';
    protected $primaryKey = 'chat_key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['chat_key','bot_active','pending_intent','pending_context','last_topic'];
    protected $casts = ['bot_active'=>'boolean'];
}
