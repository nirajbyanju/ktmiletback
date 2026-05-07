<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Auditable;

class BlogComment extends Model
{
        use HasFactory, SoftDeletes, Auditable;
    protected $table = 'blogs_comment';
    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'reply_id',
        'comment'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function post()
    {
        return $this->belongsTo(BlogPost::class, 'post_id');
    }

    public function parent()
    {
        return $this->belongsTo(BlogComment::class, 'parent_id');
    }

    public function reply()
    {
        return $this->belongsTo(BlogComment::class, 'reply_id');
    }
}
