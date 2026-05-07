<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestforPosts extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'request_for_posts';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'request_type',
        'location',
        'Budget',
        'images',
        'description',
        'status',
        'response_type_id',
        'reason',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
