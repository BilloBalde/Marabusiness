// resources/js/filament-session-timeout.js
(function() {
    // Get configuration from window object
    const panelId = window.filamentSessionPanelId || 'admin';
    const timeoutMinutes = window.filamentSessionTimeoutMinutes || 5;
    
    let idleTimer;
    let warningTimer;
    
    function resetTimers() {
        clearTimeout(idleTimer);
        clearTimeout(warningTimer);
        
        // Warning 30 seconds before logout
        warningTimer = setTimeout(showWarning, (timeoutMinutes * 60 * 1000) - 30000);
        
        // Logout after full timeout
        idleTimer = setTimeout(logout, timeoutMinutes * 60 * 1000);
    }
    
    function showWarning() {
        // Use browser notification or alert
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Session Expiring', {
                body: 'Your session will expire in 30 seconds.',
                icon: '/favicon.ico'
            });
        } else {
            console.warn('Session will expire in 30 seconds');
        }
    }
    
    function logout() {
        window.location.href = '/' + panelId + '/logout';
    }
    
    // Event listeners for user activity
    const events = ['mousemove', 'keydown', 'click', 'touchstart', 'scroll'];
    events.forEach(event => {
        document.addEventListener(event, resetTimers, { passive: true });
    });
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', resetTimers);
    } else {
        resetTimers();
    }
})();