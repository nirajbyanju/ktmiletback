<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\Data\Category;
use App\Services\TocGenerator;
use App\Auditable;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'blogs_posts';
    protected $fillable = [
        'title',
        'slug',
        'author',
        'tags',
        'category_id',
        'status',
        'content',
        'toc_structure',
        'publish_date',
        'thumbnail',
        'media',
        'is_status',
        'scheduled_publish_date',
        'view_count',
        'like_count',
        'bookmark_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'toc_structure' => 'array',
    ];

     protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($post) {
            if ($post->isDirty('content')) {
                $generator = new TocGenerator();
                $result = $generator->generateFromContent($post->content);
                
                $post->content = $result['content'];
                $post->toc_structure = $result['toc'];
            }
        });
    }

     public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class, 'post_id');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 1);
    }


    public function scopeInactive(Builder $query)
    {
        return $query->where('status', 0);
    }
}
