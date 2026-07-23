<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ApiResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_code',
        'userCode',
        'usercode',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'has_password',
        'google_id',
        'status',
        'referral_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'referral_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_password' => 'boolean',
        ];
    }

    public function setUsercodeAttribute(mixed $value): void
    {
        $this->attributes['user_code'] = $value;
    }

    public function getUsercodeAttribute(): ?string
    {
        return $this->attributes['user_code'] ?? $this->attributes['userCode'] ?? null;
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])));

        if ($name !== '') {
            return $name;
        }

        return $this->username ?: $this->email;
    }

    public function userDetail(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    /** The teacher profile linked to this account, if this user is a teacher. */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    protected static function booted(): void
    {
        // Keep a teacher's profile name/phone/email in step with their login
        // account, so a change made in either place shows everywhere (dashboard +
        // public page). saveQuietly mirrors without re-firing events (no loop).
        static::saved(function (User $user): void {
            $nameChanged = $user->wasChanged('first_name') || $user->wasChanged('last_name') || $user->wasChanged('name');
            if (! $nameChanged && ! $user->wasChanged('phone') && ! $user->wasChanged('email')) {
                return;
            }

            $teacher = $user->teacher()->first();
            if (! $teacher) {
                return;
            }

            $mirror = [];
            if ($nameChanged) {
                $full = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: (string) $user->name;
                if (filled($full)) {
                    $mirror['name'] = $full;
                }
            }
            if ($user->wasChanged('phone') && filled($user->phone)) {
                $mirror['phone'] = $user->phone;
            }
            if ($user->wasChanged('email') && filled($user->email)) {
                $mirror['email'] = $user->email;
            }

            if ($mirror) {
                $teacher->forceFill($mirror)->saveQuietly();
            }
        });
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Referrals this user made (as the referrer). */
    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.'.$this->id;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ApiResetPasswordNotification($token));
    }
}
