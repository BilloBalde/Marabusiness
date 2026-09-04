<?php

namespace App\Livewire\Auth;

use Livewire\Component;

use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Title('Login Page - MARA BUSINESS')]
class LoginPage extends Component
{

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
        $this->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
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
