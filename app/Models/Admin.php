<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Admin extends Model
{
    use HasFactory;
    protected $fillable = ['name','first_name','last_name','email','password','role','profile_picture'];
    protected $hidden = ['password'];
    protected $casts = ['password' => 'hashed'];
    public $timestamps = true;
}
