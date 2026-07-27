<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';
    protected $fillable = ['title','slug','content','category','is_published'];
    protected $casts = ['is_published'=>'boolean'];
}
