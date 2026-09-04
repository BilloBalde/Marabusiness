class CsrfTokenManager {
    constructor() {
        this.token = document.querySelector('meta[name="csrf-token"]')?.content;
        this.refreshInterval = 900000; // 15 minutes
        this.setupTokenRefresh();
        this.setupAjaxInterceptor();
    }

    setupTokenRefresh() {
        // Refresh token periodically
        setInterval(() => {
            this.refreshToken();
        }, this.refreshInterval);
    }

    setupAjaxInterceptor() {
        // Intercept all AJAX requests to add CSRF token
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            options.headers = options.headers || {};
            
            // Add CSRF token to all state-changing requests
            if (options.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method.toUpperCase())) {
                options.headers['X-CSRF-TOKEN'] = this.getToken();
                options.headers['X-Requested-With'] = 'XMLHttpRequest';
            }

            try {
                const response = await originalFetch(url, options);
                
                // Check if response contains new CSRF token
                const newToken = response.headers.get('X-CSRF-TOKEN');
                if (newToken) {
                    this.updateToken(newToken);
                }

                // Handle 419 errors silently - redirect to home
                if (response.status === 419) {
                    this.handleSessionExpired();
                    // Return a rejected promise to stop further execution
                    return Promise.reject('Session expired');
                }

                return response;
            } catch (error) {
                console.error('Fetch error:', error);
                throw error;
            }
        };

        // Setup Axios interceptor if Axios is available
        if (window.axios) {
            window.axios.interceptors.request.use(config => {
                config.headers['X-CSRF-TOKEN'] = this.getToken();
                config.headers['X-Requested-With'] = 'XMLHttpRequest';
                return config;
            });

            window.axios.interceptors.response.use(
                response => response,
                error => {
                    if (error.response?.status === 419) {
                        this.handleSessionExpired();
                    }
                    return Promise.reject(error);
                }
            );
        }
    }

    async refreshToken() {
        // Don't try to refresh if we're already on login page or home
        if (window.location.pathname === '/login' || window.location.pathname === '/') {
            return;
        }

        try {
            const response = await fetch('/refresh-csrf', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getToken()
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.token) {
                    this.updateToken(data.token);
                }
            }
        } catch (error) {
            // Silent fail - don't show anything
        }
    }

    handleSessionExpired() {
        // Clear token
        this.token = null;
        
        // Remove from meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', '');
        }
        
        // Clear any stored user data
        if (window.localStorage) {
            localStorage.removeItem('user');
            localStorage.removeItem('session');
        }
        
        if (window.sessionStorage) {
            sessionStorage.clear();
        }
        
        // Redirect to home page
        window.location.href = '/';
    }

    getToken() {
        return this.token;
    }

    updateToken(newToken) {
        this.token = newToken;
        // Update meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
    }
}

// Initialize CSRF token manager
document.addEventListener('DOMContentLoaded', () => {
    window.csrfManager = new CsrfTokenManager();
});