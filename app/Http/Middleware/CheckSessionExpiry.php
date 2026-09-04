<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $panels = ['admin', 'vendor'];
            $currentPath = $request->path();
            $currentTime = time();
            $lifetime = config('session.lifetime', 120) * 60; // Convert minutes to seconds
            
            // Determine which panel this request belongs to
            $currentPanel = null;
            foreach ($panels as $panel) {
                if (str_starts_with($currentPath, $panel) || 
                    ($request->is('livewire/update') && session('current_panel') === $panel)) {
                    $currentPanel = $panel;
                    session(['current_panel' => $panel]);
                    break;
                }
            }
            
            // Check global session expiry
            $lastActivity = session('last_activity');
            if ($lastActivity && ($currentTime - $lastActivity > $lifetime)) {
                return $this->handleSessionExpiry($request, $currentPanel);
            }
            
            // If this is a panel request, also check panel-specific expiry
            if ($currentPanel) {
                $lastPanelActivity = session("last_activity_{$currentPanel}");
                if ($lastPanelActivity && ($currentTime - $lastPanelActivity > $lifetime)) {
                    return $this->handleSessionExpiry($request, $currentPanel);
                }
                // Update panel-specific activity
                session(["last_activity_{$currentPanel}" => $currentTime]);
            }
            
            // Update global last activity time
            session(['last_activity' => $currentTime]);
        }
        
        return $next($request);
    }
    
    /**
     * Handle session expiry
     */
    protected function handleSessionExpiry(Request $request, ?string $panel = null)
    {
        // Logout the user
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Determine login route
        $loginRoute = $panel 
            ? route("filament.{$panel}.auth.login") 
            : route('login');
        
        // For API/JSON requests
        if ($request->expectsJson() || $request->header('X-Livewire')) {
            return response()->json([
                'message' => 'Your session has expired.',
                'redirect' => $loginRoute
            ], 419);
        }
        
        // For web requests
        return redirect($loginRoute)
            ->with('error', 'Your session has expired. Please login again.');
    }
}