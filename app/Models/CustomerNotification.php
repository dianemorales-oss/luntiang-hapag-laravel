<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    protected $table = 'customer_notifications';
    protected $fillable = ['user_id','type','title','message','related_id','related_type','link','is_read'];
    protected $casts = ['is_read'=>'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
