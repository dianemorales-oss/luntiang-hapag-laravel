<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Admin extends Model
{
    use HasFactory;
    protected $fillable = ['name','email','password','role'];
    protected $hidden = ['password'];
    protected $casts = ['password' => 'hashed'];
    public $timestamps = true;
}
