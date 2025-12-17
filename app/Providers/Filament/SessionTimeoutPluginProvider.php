<?php
// app/Providers/Filament/SessionTimeoutPluginProvider.php
namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Illuminate\Support\Facades\Blade;

class SessionTimeoutPluginProvider implements Plugin
{
    public function getId(): string
    {
        return 'session-timeout';
    }
    
    public function register(Panel $panel): void
    {
        $panel->middleware([
            \App\Http\Middleware\MultiPanelSessionTimeout::class,
        ]);
        
        // Add inline script at the end of body
        $panel->renderHook(
            'panels::body.end',
            fn () => $this->getScriptContent($panel->getId())
        );
    }
    
    public function boot(Panel $panel): void
    {
        // Nothing needed
    }
    
    protected function getScriptContent(string $panelId): string
    {
        $script = <<<JS
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var timeoutMinutes = 5;
                    var timeoutMs = timeoutMinutes * 60 * 1000;
                    var warningMs = timeoutMs - 30000; // 30 seconds warning
                    
                    var logoutTimer;
                    var warningTimer;
                    
                    function resetTimers() {
                        clearTimeout(logoutTimer);
                        clearTimeout(warningTimer);
                        
                        warningTimer = setTimeout(showWarning, warningMs);
                        logoutTimer = setTimeout(logout, timeoutMs);
                    }
                    
                    function showWarning() {
                        // Simple confirmation
                        setTimeout(function() {
                            if (confirm('Your session will expire in 30 seconds. Click OK to stay logged in.')) {
                                resetTimers();
                            }
                        }, 5000);
                    }
                    
                    function logout() {
                        window.location.href = '/{$panelId}/logout';
                    }
                    
                    // Reset on user activity
                    ['mousemove', 'keydown', 'click', 'touchstart'].forEach(function(event) {
                        document.addEventListener(event, resetTimers);
                    });
                    
                    // Start the timers
                    resetTimers();
                });
            </script>
        JS;
        
        return $script;
    }
    
    public static function make(): static
    {
        return app(static::class);
    }
}