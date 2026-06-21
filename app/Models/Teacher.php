<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'teacher_id',
        'name',
        'phone',
        'email',
        // Legacy single-course string kept for backward compat; prefer teacher_courses pivot
        'course',
        'available_time',
        'qualification',
        'specialization',
        'experience_years',
        'bio',
        'available_days',
        'available_from',
        'available_to',
        'status',
        'notes',
        'profile_photo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'user_id'          => 'integer',
        'experience_years' => 'integer',
        'available_days'   => 'array',
        'created_by'       => 'integer',
        'updated_by'       => 'integer',
        'deleted_by'       => 'integer',
    ];

    protected $appends = ['profile_photo_url', 'display_name', 'display_email', 'display_phone'];

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        if (Str::startsWith($this->profile_photo, ['http://', 'https://'])) {
            return $this->profile_photo;
        }

        return Storage::disk('public')->url($this->profile_photo);
    }

    /** Display name: teacher's own name field, falls back to linked user's name. */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->relationLoaded('user') && $this->user) {
            return trim($this->user->first_name . ' ' . $this->user->last_name)
                ?: ($this->user->name ?? $this->user->email ?? 'Unknown');
        }

        return 'Unknown';
    }

    /** Display email: own field, falls back to user. */
    public function getDisplayEmailAttribute(): ?string
    {
        return $this->email ?: ($this->relationLoaded('user') ? $this->user?->email : null);
    }

    /** Display phone: own field, falls back to user. */
    public function getDisplayPhoneAttribute(): ?string
    {
        return $this->phone ?: ($this->relationLoaded('user') ? $this->user?->phone : null);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Courses this teacher is assigned to. */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'teacher_courses')
                    ->withTimestamps();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Generate next auto teacher_id (T-001, T-002 …). */
    public static function nextTeacherId(): string
    {
        $max = static::withTrashed()->max('id') ?? 0;
        return 'T-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }
}
