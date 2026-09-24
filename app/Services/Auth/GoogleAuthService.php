<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    public function getGoogleRedirectUrl(): string
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    public function handleGoogleCallback(): array
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user && ! $user->hasRole('Owner')) {
            throw ValidationException::withMessages([
                'email' => 'You are not authorized to log in with Google.',
            ]);
        }

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'role' => 'Owner',
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('Owner');
        } else {
            $user->update([
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
            ]);
        }

        $token = auth('api')->login($user);

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) auth('api')->factory()->getTTL() / 60 .' hours',
            'user' => $user,
        ];
    }
}
