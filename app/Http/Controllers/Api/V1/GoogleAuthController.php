<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends BaseController
{
    public function redirect()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $provider */
        $provider = Socialite::driver('google');
        return $provider->stateless()->redirect();
    }

    public function callback()
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        /** @var \Laravel\Socialite\Two\GoogleProvider $provider */
        $provider = Socialite::driver('google');

        try {
            $googleUser = $provider->stateless()->user();
        } catch (\Exception) {
            return redirect("$frontendUrl/login?error=google_auth_failed");
        }

        // Find existing user by google_id first, then fall back to email
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // New user — create from Google profile
            $nameParts  = preg_split('/\s+/', trim((string) $googleUser->getName()), 2) ?: ['User'];
            $firstName  = $nameParts[0];
            $lastName   = $nameParts[1] ?? $firstName;
            $fullName   = trim("$firstName $lastName");
            $emailLocal = Str::before((string) $googleUser->getEmail(), '@');
            $base       = Str::lower(preg_replace('/[^A-Za-z0-9]+/', '', $emailLocal) ?: 'user');
            $username   = $this->uniqueUsername($base);
            $userCode   = 'Opsh-' . now()->year . '-' . ((User::max('id') ?? 0) + 1);

            $user = User::create([
                'user_code'    => $userCode,
                'name'         => $fullName,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'username'     => $username,
                'email'        => $googleUser->getEmail(),
                'google_id'    => $googleUser->getId(),
                'has_password' => false,
                'status'       => 1,
            ]);

            $user->assignRole('User');
        } else {
            // Existing user — link google_id if not yet set
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        }

        $user->load('roles');

        $deviceName = 'google-oauth';
        RefreshToken::revokeDeviceSessionsForUser($user, $deviceName);

        $accessTokenExpiresAt = Carbon::now()->addHour();
        $newAccessToken       = $user->createToken($deviceName, ['*'], $accessTokenExpiresAt);
        $refreshToken         = RefreshToken::createForUser(
            $user,
            $newAccessToken->accessToken,
            $deviceName,
            null,
            null,
        );

        $payload = [
            'token'                    => $newAccessToken->plainTextToken,
            'access_token'             => $newAccessToken->plainTextToken,
            'refresh_token'            => $refreshToken->token,
            'expires_in'               => now()->diffInSeconds($accessTokenExpiresAt, false),
            'access_token_expires_at'  => $accessTokenExpiresAt->toISOString(),
            'refresh_token_expires_at' => $refreshToken->expires_at?->toISOString(),
            'token_type'               => 'Bearer',
            'needs_password'           => !$user->has_password,
            'user' => [
                'id'           => $user->id,
                'firstName'    => $user->first_name,
                'lastName'     => $user->last_name,
                'userName'     => $user->username,
                'email'        => $user->email,
                'has_password' => (bool) $user->has_password,
                'roles'        => $user->roles->pluck('name')->values()->all(),
            ],
        ];

        $session = base64_encode(json_encode($payload));

        return redirect("$frontendUrl/auth/google/callback?session=" . urlencode($session));
    }

    private function uniqueUsername(string $base): string
    {
        $username = $base;
        $counter  = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter++;
        }
        return $username;
    }
}
