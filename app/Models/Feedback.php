<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Feedback extends Model
{
    protected $table = 'feedback';
    protected $fillable = ['user_id','guest_name','guest_email','subject','rating','comments'];
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    public function user(){ return $this->belongsTo(User::class); }
}
