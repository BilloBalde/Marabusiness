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

            // Matched by (provider, provider_id) first — an account already linked
            // to this exact social identity. Failing that, by email: Google verifies
            // email ownership before handing out a token, so completing this flow
            // means genuinely controlling that mailbox — the same trust "forgot
            // password" already relies on. Grouped explicitly (rather than a bare
            // ->orWhere('email', ...) after two ->where() calls) so this stays
            // (provider AND provider_id) OR email regardless of what a future edit
            // adds above it — a silent regrouping there would let an unrelated
            // ->where() upstream turn this into "match everyone with this email".
            //
            // Restricted to customer accounts: the Google button only ever appears
            // on the public login page, never an admin or vendor login screen, so
            // this lookup should not be able to reach a staff account even if it
            // happens to share the exact verified mailbox. This same restriction
            // will matter more the day Facebook login is actually wired up (its
            // routes exist, its controller methods don't yet) — Facebook does not
            // guarantee a verified email the way Google does, so email-matching
            // needs its own look before it is trusted for that provider.
            $user = User::where(function ($query) use ($provider, $socialUser) {
                    $query->where('provider', $provider)
                        ->where('provider_id', $socialUser->getId());
                })
                ->orWhere(function ($query) use ($socialUser) {
                    $query->where('email', $socialUser->getEmail())
                        ->role('customer');
                })
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
                // Web: normal login and redirect. Auth::login() does not itself
                // rotate the session id — regenerate() here is what stops someone
                // who planted a session on this browser beforehand (a shared
                // computer, a fixation link) from inheriting it once the real user
                // signs in.
                Auth::login($user, true);
                session()->regenerate();
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