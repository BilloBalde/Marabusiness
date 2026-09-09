<style>
    .session-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(5px);
    }

    .session-modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .session-modal {
        background: white;
        border-radius: 20px;
        padding: 30px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        transform: scale(0.9);
        transition: transform 0.3s ease;
        text-align: center;
    }

    .session-modal-overlay.active .session-modal {
        transform: scale(1);
    }

    .session-modal-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        animation: warningPulse 2s infinite;
    }

    @keyframes warningPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .session-modal h3 {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    .session-modal p {
        color: #666;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .session-modal-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .session-modal-button {
        padding: 12px 30px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        outline: none;
        flex: 1;
    }

    .session-modal-button.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .session-modal-button.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .session-modal-button.secondary {
        background: #f1f1f1;
        color: #666;
    }

    .session-modal-button.secondary:hover {
        background: #e5e5e5;
    }
</style>

<script>
document.addEventListener('livewire:init', () => {
    let sessionTimeout;
    let warningTimeout;
    let modalElement = null;
    
    const sessionLifetime = {{ config('session.lifetime', 120) }} * 60 * 1000;
    const warningTime = sessionLifetime - (5 * 60 * 1000);
    const loginUrl = '{{ route('login') }}';
    
    function createModal() {
        if (document.getElementById('session-modal')) {
            return document.getElementById('session-modal');
        }
        
        const overlay = document.createElement('div');
        overlay.id = 'session-modal';
        overlay.className = 'session-modal-overlay';
        overlay.innerHTML = `
            <div class="session-modal">
                <div class="session-modal-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                    </svg>
                </div>
                <h3>Session About to Expire</h3>
                <p>Your session will expire in 5 minutes due to inactivity. Would you like to continue?</p>
                <div class="session-modal-buttons">
                    <button class="session-modal-button primary" id="stay-logged-in">Stay Logged In</button>
                    <button class="session-modal-button secondary" id="logout-now">Logout</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        document.getElementById('stay-logged-in').addEventListener('click', extendSession);
        document.getElementById('logout-now').addEventListener('click', () => {
            modalElement.classList.remove('active');
            window.location.href = loginUrl;
        });
        
        return overlay;
    }
    
    function showSessionWarning() {
        modalElement = createModal();
        modalElement.classList.add('active');
    }
    
    function extendSession() {
        fetch('/keep-alive', { 
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                modalElement.classList.remove('active');
                resetSessionTimeout();
            }
        })
        .catch(() => {
            window.location.href = loginUrl;
        });
    }
    
    const resetSessionTimeout = () => {
        clearTimeout(warningTimeout);
        clearTimeout(sessionTimeout);
        
        if (modalElement) {
            modalElement.classList.remove('active');
        }
        
        warningTimeout = setTimeout(showSessionWarning, warningTime);
        sessionTimeout = setTimeout(() => {
            window.location.href = loginUrl;
        }, sessionLifetime);
    };
    
    ['click', 'keypress', 'scroll', 'mousemove', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetSessionTimeout);
    });
    
    resetSessionTimeout();
});
</script>