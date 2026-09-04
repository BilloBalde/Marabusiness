<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class MultiPanelSessionTimeout
{
    public function handle(Request $request, Closure $next)
    {
        // This middleware can be simplified to just handle panel-specific
        // logic since CheckSessionExpiry now handles the expiry checking
        
        $panels = ['admin', 'vendor'];
        $currentPath = $request->path();
        
        // Just track which panel we're in
        foreach ($panels as $panel) {
            if (str_starts_with($currentPath, $panel) || 
                ($request->is('livewire/update') && session('current_panel') === $panel)) {
                session(['current_panel' => $panel]);
                break;
            }
        }
        
        return $next($request);
    }
}