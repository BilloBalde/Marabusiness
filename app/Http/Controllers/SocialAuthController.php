<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    public function redirectGoogle(Request $request)
    {
        // If the request comes from mobile, we'll handle it differently in the callback
        // but we don't need to change the redirect itself.
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callbackGoogle(Request $request)
    {
        $platform = $request->query('platform', 'web'); // default to web
        return $this->handleCallback('google', $platform);
    }

    private function handleCallback($provider, $platform)
    {
        try {
            Socialite::driver($provider)->setHttpClient(
                new \GuzzleHttp\Client(['proxy' => null])
            );

            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Find or create user
            $user = User::where('provider', $provider)
                    ->where('provider_id', $socialUser->getId())
                    ->orWhere('email', $socialUser->getEmail())
                    ->first();

            if ($user) {
                $user->update([
                    'name' => $socialUser->getName(),
                    'avatar' => $socialUser->getAvatar() ?: $user->avatar,
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            } else {
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'password' => bcrypt(uniqid()),
                ]);
                $user->assignRole('customer');
            }

            // Generate Sanctum token (for mobile)
            $token = $user->createToken('mobile-auth')->plainTextToken;

            // Prepare user data
            $userData = $user->only(['id', 'name', 'email', 'phone', 'avatar', 'roles']);
            $userData['token'] = $token;

            if ($platform === 'mobile') {
                // Mobile: redirect to custom scheme with base64-encoded data
                $encoded = base64_encode(json_encode($userData));
                return redirect()->away("mara://login/callback?data=$encoded");
            } else {
                // Web: normal login and redirect
                Auth::login($user, true);
                return redirect('/'); // or redirect()->intended('/')
            }

        } catch (\Exception $e) {
            \Log::error("Social login error: " . $e->getMessage());
            if ($platform === 'mobile') {
                return redirect()->away("mara://login/error?message=" . urlencode($e->getMessage()));
            } else {
                return redirect('/login')->with('error', 'Authentication failed. Please try again.');
            }
        }
    }
}