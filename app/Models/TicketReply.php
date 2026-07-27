<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketReply extends Model
{
    protected $table = 'ticket_replies';
    protected $fillable = ['ticket_id','sender_type','message'];
    public $timestamps = false;
    protected $casts = ['created_at'=>'datetime'];
    public function ticket(){ return $this->belongsTo(Ticket::class); }
}
