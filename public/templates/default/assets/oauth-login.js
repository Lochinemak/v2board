/**
 * OAuth Login Component for V2Board
 * 
 * This file provides OAuth login functionality for the frontend
 */

class OAuthLogin {
    constructor() {
        this.providers = {};
        this.init();
    }

    /**
     * Initialize OAuth login component
     */
    async init() {
        try {
            await this.loadProviders();
            this.renderOAuthButtons();
            this.bindEvents();
        } catch (error) {
            console.error('Failed to initialize OAuth login:', error);
        }
    }

    /**
     * Load available OAuth providers from backend
     */
    async loadProviders() {
        try {
            const response = await fetch('/api/v1/passport/oauth/providers');
            const result = await response.json();
            
            if (response.ok && result.data) {
                this.providers = result.data;
            } else {
                console.warn('No OAuth providers configured');
            }
        } catch (error) {
            console.error('Failed to load OAuth providers:', error);
        }
    }

    /**
     * Render OAuth login buttons
     */
    renderOAuthButtons() {
        const container = document.getElementById('oauth-login-container');
        if (!container || Object.keys(this.providers).length === 0) {
            return;
        }

        let buttonsHtml = '<div class="oauth-login-section">';
        buttonsHtml += '<div class="oauth-divider"><span>或使用第三方账号登录</span></div>';
        buttonsHtml += '<div class="oauth-buttons">';

        Object.keys(this.providers).forEach(providerKey => {
            const provider = this.providers[providerKey];
            buttonsHtml += this.createOAuthButton(providerKey, provider);
        });

        buttonsHtml += '</div></div>';
        container.innerHTML = buttonsHtml;
    }

    /**
     * Create OAuth button HTML
     */
    createOAuthButton(providerKey, provider) {
        const iconClass = this.getProviderIconClass(providerKey);
        return `
            <button class="oauth-btn oauth-btn-${providerKey}" data-provider="${providerKey}">
                <i class="${iconClass}"></i>
                <span>使用 ${provider.name} 登录</span>
            </button>
        `;
    }

    /**
     * Get provider icon class
     */
    getProviderIconClass(providerKey) {
        const iconMap = {
            'github': 'fab fa-github',
            'google': 'fab fa-google',
            'oauth_provider': 'fas fa-sign-in-alt'
        };
        return iconMap[providerKey] || 'fas fa-sign-in-alt';
    }

    /**
     * Bind click events to OAuth buttons
     */
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.oauth-btn')) {
                const button = e.target.closest('.oauth-btn');
                const provider = button.getAttribute('data-provider');
                this.handleOAuthLogin(provider);
            }
        });

        // Handle OAuth callback parameters
        this.handleOAuthCallback();
    }

    /**
     * Handle OAuth login button click
     */
    handleOAuthLogin(provider) {
        if (!this.providers[provider]) {
            console.error('Invalid OAuth provider:', provider);
            return;
        }

        // Store current page for redirect after login
        const currentPath = window.location.hash || '#/dashboard';
        
        // Redirect to OAuth provider
        const redirectUrl = `/api/v1/passport/oauth/${provider}/redirect?redirect=${encodeURIComponent(currentPath)}`;
        window.location.href = redirectUrl;
    }

    /**
     * Handle OAuth callback parameters
     */
    handleOAuthCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const authData = urlParams.get('auth_data');
        const token = urlParams.get('token');
        const error = urlParams.get('oauth_error');

        if (error) {
            this.showError('OAuth登录失败: ' + decodeURIComponent(error));
            // Clean URL
            this.cleanUrl();
            return;
        }

        if (authData && token) {
            // Store auth data
            localStorage.setItem('auth_data', authData);
            localStorage.setItem('token', token);
            
            // Show success message
            this.showSuccess('登录成功！');
            
            // Clean URL and redirect
            this.cleanUrl();
            
            // Trigger login success event
            window.dispatchEvent(new CustomEvent('oauth-login-success', {
                detail: { authData, token }
            }));
            
            // Redirect to dashboard or intended page
            setTimeout(() => {
                window.location.hash = '#/dashboard';
                window.location.reload();
            }, 1000);
        }
    }

    /**
     * Clean URL parameters
     */
    cleanUrl() {
        const url = new URL(window.location);
        url.search = '';
        window.history.replaceState({}, document.title, url.toString());
    }

    /**
     * Show error message
     */
    showError(message) {
        // You can customize this based on your UI framework
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, 'error');
        } else {
            alert(message);
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // You can customize this based on your UI framework
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, 'success');
        } else {
            alert(message);
        }
    }
}

// CSS styles for OAuth buttons
const oauthStyles = `
<style>
.oauth-login-section {
    margin-top: 20px;
}

.oauth-divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
}

.oauth-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e0e0e0;
}

.oauth-divider span {
    background: white;
    padding: 0 15px;
    color: #666;
    font-size: 14px;
}

.oauth-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.oauth-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 20px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.oauth-btn:hover {
    background: #f5f5f5;
    border-color: #ccc;
}

.oauth-btn-github {
    border-color: #333;
}

.oauth-btn-github:hover {
    background: #333;
    color: white;
}

.oauth-btn-google {
    border-color: #db4437;
}

.oauth-btn-google:hover {
    background: #db4437;
    color: white;
}

.oauth-btn i {
    font-size: 16px;
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', oauthStyles);

// Initialize OAuth login when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new OAuthLogin();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OAuthLogin;
}
