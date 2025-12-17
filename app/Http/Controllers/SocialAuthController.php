<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callbackGoogle()
    {
        return $this->handleCallback('google');
    }

    public function redirectFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function callbackFacebook()
    {
        return $this->handleCallback('facebook');
    }

    private function handleCallback($provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = User::updateOrCreate(
            [
                'provider'    => $provider,
                'provider_id' => $socialUser->getId(),
            ],
            [
                'name'   => $socialUser->getName(),
                'email'  => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'password' => bcrypt(uniqid()),
            ]
        );

        if (!$user->hasRole('customer')) {
            $user->assignRole('customer');
        }

        Auth::login($user, true);
        return redirect('/');
    }
}
