<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Ticket extends Model
{
    protected $fillable = ['user_id','subject','category','priority','order_number','issue_description','attachment_path','status','admin_reply','replied_at'];
    protected $casts = ['replied_at'=>'datetime'];
    public function user(){ return $this->belongsTo(User::class); }
    public function replies(){ return $this->hasMany(TicketReply::class); }
}
