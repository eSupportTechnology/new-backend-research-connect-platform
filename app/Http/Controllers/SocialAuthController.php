<?php

namespace App\Http\Controllers;

use App\Models\RegisterUsers\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
            return $this->handleOAuthUser($socialUser, 'google');
        } catch (\Exception $e) {
            return $this->oauthError('Google login failed. Please try again.');
        }
    }

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $socialUser = Socialite::driver('facebook')->stateless()->user();
            return $this->handleOAuthUser($socialUser, 'facebook');
        } catch (\Exception $e) {
            return $this->oauthError('Facebook login failed. Please try again.');
        }
    }

    private function handleOAuthUser($socialUser, string $provider)
    {
        $providerIdField = "{$provider}_id";

        // Find by provider ID first, then fall back to email
        $user = User::where($providerIdField, $socialUser->getId())->first()
            ?? User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            $nameParts = explode(' ', trim($socialUser->getName() ?? ''), 2);
            $user = User::create([
                'first_name'      => $nameParts[0] ?? 'User',
                'last_name'       => $nameParts[1] ?? '',
                'email'           => $socialUser->getEmail(),
                $providerIdField  => $socialUser->getId(),
                'oauth_provider'  => $provider,
                'role'            => 'GENERAL_USER',
                'status'          => 'Active',
                'password'        => Hash::make(Str::random(32)),
            ]);
        } else {
            $user->update([
                $providerIdField => $socialUser->getId(),
                'oauth_provider' => $provider,
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = urlencode(json_encode([
            'id'              => $user->id,
            'first_name'      => $user->first_name,
            'last_name'       => $user->last_name,
            'email'           => $user->email,
            'role'            => $user->role,
            'user_type'       => $user->user_type,
            'status'          => $user->status,
            'membership_tier' => $user->membership_tier ?? 'bronze',
        ]));

        $frontendUrl = rtrim(env('FRONTEND_URL'), '/');
        return redirect("{$frontendUrl}/auth/callback?token={$token}&user={$userData}");
    }

    private function oauthError(string $message)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL'), '/');
        return redirect("{$frontendUrl}/login?error=" . urlencode($message));
    }
}