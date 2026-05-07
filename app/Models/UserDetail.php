<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Auditable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserDetail extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'user_details';

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'bio',
        'profile_picture',
        'gender',
        'country',
        'state',
        'district',
        'local_bodies',
        'street_name',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return null;
        }

        if (Str::startsWith($this->profile_picture, ['http://', 'https://'])) {
            return $this->profile_picture;
        }

        return Storage::disk('public')->url($this->profile_picture);
    }
}
