<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Services\TocGenerator;
use App\Auditable;
use App\Models\BlogPost;

class Category extends Model
{
     use SoftDeletes, Auditable ;
    protected $table = 'blogs_category';

    protected $fillable = [
        'label',
        'slug',
    ];

    public function blogPosts()
    {
        return $this->hasMany(BlogPost::class);
    }
}
