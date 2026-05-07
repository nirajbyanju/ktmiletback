<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'personal_access_token_id',
        'token',
        'device_name',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accessToken()
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function createForUser(
        User $user,
        ?PersonalAccessToken $accessToken = null,
        ?string $deviceName = null,
        ?string $userAgent = null,
        ?string $ipAddress = null,
    ): self
    {
        return self::create([
            'user_id' => $user->id,
            'personal_access_token_id' => $accessToken?->id,
            'token' => self::generateToken(),
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => Carbon::now()->addDays(30), // 30 days expiry
        ]);
    }

    public static function revokeDeviceSessionsForUser(User $user, string $deviceName): void
    {
        $sessions = self::query()
            ->where('user_id', $user->id)
            ->where('device_name', $deviceName)
            ->get();

        $accessTokenIds = $sessions
            ->pluck('personal_access_token_id')
            ->filter()
            ->unique()
            ->values();

        if ($accessTokenIds->isNotEmpty()) {
            PersonalAccessToken::query()
                ->whereIn('id', $accessTokenIds)
                ->delete();
        }

        self::query()
            ->whereIn('id', $sessions->pluck('id'))
            ->delete();
    }
}
