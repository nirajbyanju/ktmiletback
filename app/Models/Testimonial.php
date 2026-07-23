<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'initials',
        'name',
        'meta',
        'tag',
        'country',
        'quote',
        'cats',
        'is_featured',
        'sort_order',
        'photo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'cats'        => 'array',
        'is_featured' => 'boolean',
        'sort_order'  => 'integer',
        'created_by'  => 'integer',
        'updated_by'  => 'integer',
        'deleted_by'  => 'integer',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        // Served through the API so it works even when the /storage
        // symlink is missing on the host (same approach as profile pictures).
        // The ?v= timestamp changes whenever the record updates, so browsers
        // never show a stale cached copy after a photo is replaced.
        $version = $this->updated_at?->timestamp ?? 0;

        return url("/api/v1/public/testimonials/{$this->id}/photo") . "?v={$version}";
    }
}
