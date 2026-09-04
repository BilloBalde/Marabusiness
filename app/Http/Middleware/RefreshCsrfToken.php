<?php
// app/Http/Middleware/RefreshCsrfToken.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RefreshCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Handle AJAX/JSON requests
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Livewire')) {
            
            // Check if session is still valid
            if (Auth::check()) {
                $lastActivity = session('last_activity');
                $lifetime = config('session.lifetime', 120) * 60;
                
                // If session is about to expire in the next 5 minutes, refresh it
                if ($lastActivity && (time() - $lastActivity > ($lifetime - 300))) {
                    // Update session activity to keep it alive
                    session(['last_activity' => time()]);
                    
                    // Log the refresh for debugging
                    Log::info('CSRF Token refreshed for user', [
                        'user_id' => Auth::id(),
                        'ip' => $request->ip(),
                    ]);
                }
            }
            
            // Add CSRF token to response headers for Livewire/AJAX
            if ($request->header('X-Livewire')) {
                // For Livewire, we'll add the token to the response headers
                return $next($request)->header('X-CSRF-TOKEN', csrf_token());
            }
        }
        
        // For regular form submissions, check if token is about to expire
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            $this->validateTokenExpiry($request);
        }
        
        return $next($request);
    }
    
    /**
     * Validate if the CSRF token is still valid
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateTokenExpiry(Request $request)
    {
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        
        if (!$token) {
            return;
        }
        
        // Check if token exists in session
        if (!hash_equals($request->session()->token(), $token)) {
            // Token mismatch - generate new one
            $request->session()->regenerateToken();
            
            Log::warning('CSRF token regenerated due to mismatch', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
            ]);
        }
    }
    
    /**
     * Handle response after request is processed
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return \Illuminate\Http\Response
     */
    public function terminate($request, $response)
    {
        // For Livewire responses, ensure CSRF token is included
        if ($request->header('X-Livewire') && method_exists($response, 'header')) {
            $response->header('X-CSRF-TOKEN', csrf_token());
        }
    }
}