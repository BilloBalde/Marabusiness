<?php
// app/Http/Middleware/MultiPanelSessionTimeout.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;

class MultiPanelSessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('MultiPanelSessionTimeout triggered', [
        'path' => $request->path(),
        'method' => $request->method(),
        'is_livewire' => $request->is('livewire/*'),
        'headers' => $request->headers->all(),
    ]);
        $panels = ['admin', 'vendor']; // Your panel IDs
        $currentPath = $request->path();
        
        // Check if request is for any Filament panel
        $isFilamentPanel = false;
        $panelId = null;
        
        foreach ($panels as $panel) {
            if (str_starts_with($currentPath, $panel)) {
                $isFilamentPanel = true;
                $panelId = $panel;
                break;
            }
        }
        
        if ($isFilamentPanel && $panelId) {
            $lastActivity = session("last_activity_{$panelId}");
            $currentTime = time();
            
            // Check if session expired (5 minutes = 300 seconds)
            if ($lastActivity && ($currentTime - $lastActivity > 300)) {
                // Get the panel instance
                $panel = Filament::getPanel($panelId);
                
                // Check if user is authenticated in this panel
                if ($panel->auth()->check()) {
                    // Logout from this panel
                    $panel->auth()->logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    
                    // Redirect to the correct panel's login page
                    return redirect()->route("filament.{$panelId}.auth.login")
                        ->with('error', 'Your session has expired. Please login again.');
                }
            }
            
            // Update last activity time for this panel
            session(["last_activity_{$panelId}" => $currentTime]);
        }
        
        return $next($request);
    }
}
