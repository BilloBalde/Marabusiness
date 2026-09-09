<?php

namespace App\Livewire\Auth;

use Livewire\Component;

use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

#[Title('Login Page - MARA BUSINESS')]
class LoginPage extends Component
{
    // Same package Filament's own admin/vendor login already uses for this exact
    // problem (it's a transitive dependency of filament/filament, already vendored)
    // — one throttling idiom in the codebase instead of two.
    use WithRateLimiting;

    public $email = '';
    public $password = '';
    public $remember = false;

    public function mount()
    {
        // Store current URL for redirect after login
        $redirectUrl = request()->query('redirect', url()->previous());
        $currentUrl = url()->current();
        
        // Only store if previous URL is different from login and is from our app
        if ($redirectUrl !== $currentUrl && 
            $this->isValidRedirect($redirectUrl)) {
            session()->put('url.intended', $redirectUrl);
        }
    }

    public function save()
    {
        // Checked before validation and before Auth::attempt() — same order as
        // Filament's own Login::authenticate(), and for the same reason: a wrong
        // guess still counts against the limit even if the form itself is otherwise
        // valid, and a blocked attempt should never touch the database at all.
        try {
            $this->rateLimit(5); // même seuil que le login Filament : 5 tentatives / 60s
        } catch (TooManyRequestsException $exception) {
            session()->flash('error',
                "Trop de tentatives. Réessayez dans {$exception->secondsUntilAvailable} secondes.");

            return;
        }

        $this->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            // A correct password means this email+IP pair is no longer suspect —
            // the same moment the session id gets rotated below.
            $this->clearRateLimiter();

            // Auth::attempt() does not itself rotate the session id — this is what
            // stops someone who planted a session on this browser beforehand (a
            // shared computer, a fixation link) from inheriting it once the real
            // user signs in. Session data (including url.intended, set in mount())
            // survives a regenerate(); only the id and CSRF token change.
            session()->regenerate();

            // Get redirect URL from session
            $redirectTo = session()->pull('url.intended', null);
            
            // Check if it's a valid redirect (not auth pages)
            if ($redirectTo && $this->isValidRedirect($redirectTo)) {
                // For cart page, preserve selected items
                if (str_contains($redirectTo, '/cart')) {
                    // Get selected items from session if they exist
                    $selectedItems = session()->get('cart_selected_items', []);
                    if (!empty($selectedItems)) {
                        // You can pass them as query params or keep in session
                        $redirectTo .= '?selected=' . urlencode(implode(',', $selectedItems));
                    }
                }
                
                return redirect()->to($redirectTo);
            }
            
            // Default redirect based on role
            /** @var \App\Models\User|\Spatie\Permission\Traits\HasRoles $sender */
            if (Auth::user()->hasRole('admin')) {
                return redirect()->route('filament.admin.pages.dashboard');
            }
            
            return redirect('/');
        } else {
            session()->flash('error', 'Invalid credentials');
        }
    }

    /**
     * WithRateLimiting keys by component+method+IP alone by default — the same key
     * Filament's own admin/vendor login uses. That is fine for a page with no
     * concept of "which account", but here it would let one email address on a
     * shared connection lock out every other customer behind the same IP/NAT. Keying
     * by email+IP instead means only repeated wrong guesses against the *same*
     * account trip the limit; a different customer's own attempts, from the same
     * network, are never affected by someone else's failed logins.
     */
    protected function getRateLimitKey($method, $component = null)
    {
        $component ??= static::class;

        return 'livewire-rate-limiter:'.sha1(
            $component.'|'.$method.'|'.strtolower(trim($this->email)).'|'.request()->ip()
        );
    }

    private function isValidRedirect($url)
    {
        $parsed = parse_url($url);
        
        // Must be valid URL
        if (!$parsed) {
            return false;
        }
        
        // Must be same domain
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if (isset($parsed['host']) && $parsed['host'] !== $appHost) {
            return false;
        }
        
        // Must not be auth route
        $path = $parsed['path'] ?? '';
        $blockedPaths = ['/login', '/register', '/logout', '/password'];
        foreach ($blockedPaths as $blocked) {
            if (str_starts_with($path, $blocked)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
