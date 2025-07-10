// Admin OAuth Login Integration
(function() {
    'use strict';

    console.log('Admin OAuth: Script loaded');
    console.log('Admin OAuth: Settings available:', !!window.settings);
    console.log('Admin OAuth: OAuth providers:', window.settings?.oauth_providers);

    // 创建OAuth登录按钮
    function createOAuthButtons() {
        const oauthProviders = window.settings?.oauth_providers;
        if (!oauthProviders || Object.keys(oauthProviders).length === 0) {
            console.log('Admin OAuth: No OAuth providers configured');
            return null;
        }
        
        const container = document.createElement('div');
        container.className = 'admin-oauth-container';
        
        // 添加分割线
        const divider = document.createElement('div');
        divider.className = 'oauth-divider';
        divider.innerHTML = '<span>或使用第三方登录</span>';
        container.appendChild(divider);
        
        // 创建按钮容器
        const buttonsContainer = document.createElement('div');
        buttonsContainer.className = 'oauth-buttons';
        
        // 为每个OAuth提供商创建按钮
        Object.keys(oauthProviders).forEach(providerKey => {
            const provider = oauthProviders[providerKey];
            const button = document.createElement('a');
            button.className = `oauth-btn oauth-btn-${providerKey}`;

            // 为管理员OAuth添加admin参数
            const adminOAuthUrl = provider.url + (provider.url.includes('?') ? '&' : '?') + 'redirect=admin';
            button.href = adminOAuthUrl;

            // 为Linux.do使用特殊的SVG图标
            let iconHtml = '';
            if (providerKey === 'linuxdo') {
                const clipId = `linuxdo-clip-${Date.now()}-${Math.random().toString(36).substring(2, 11)}`;
                iconHtml = `
                    <svg class="oauth-icon" width="16" height="16" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <clipPath id="${clipId}">
                                <circle cx="60" cy="60" r="47"/>
                            </clipPath>
                        </defs>
                        <circle fill="#f0f0f0" cx="60" cy="60" r="50"/>
                        <rect fill="#1c1c1e" clip-path="url(#${clipId})" x="10" y="10" width="100" height="30"/>
                        <rect fill="#f0f0f0" clip-path="url(#${clipId})" x="10" y="40" width="100" height="40"/>
                        <rect fill="#ffb003" clip-path="url(#${clipId})" x="10" y="80" width="100" height="30"/>
                    </svg>
                `;
            } else {
                iconHtml = `<i class="${provider.icon}"></i>`;
            }

            button.innerHTML = `
                ${iconHtml}
                <span>使用 ${provider.name} 登录</span>
            `;

            // 添加点击事件处理
            button.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Admin OAuth: Redirecting to', provider.name, 'with admin redirect');
                window.location.href = adminOAuthUrl;
            });

            buttonsContainer.appendChild(button);
        });
        
        container.appendChild(buttonsContainer);
        return container;
    }

    // 等待元素出现的辅助函数
    function waitForElement(selector, callback, maxAttempts = 50) {
        let attempts = 0;
        const interval = setInterval(() => {
            const element = document.querySelector(selector);
            attempts++;

            if (element) {
                clearInterval(interval);
                console.log('Admin OAuth: Found element with selector:', selector);
                callback(element);
            } else if (attempts >= maxAttempts) {
                clearInterval(interval);
                console.log('Admin OAuth: Element not found after maximum attempts:', selector);
            }
        }, 200);
    }

    // 调试函数：检查页面结构
    function debugPageStructure() {
        console.log('Admin OAuth: Debugging page structure...');

        // 检查所有可能的容器
        const selectors = [
            '.block-content.block-content-full',
            '.block-content',
            '.ant-form',
            'form',
            '.form-group',
            'button[type="submit"]',
            '.btn-primary'
        ];

        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            console.log(`Admin OAuth: Found ${elements.length} elements with selector "${selector}"`);
            elements.forEach((el, index) => {
                console.log(`Admin OAuth: Element ${index}:`, el);
            });
        });
    }

    // 检查是否在登录页面
    function isLoginPage() {
        // 检查URL路径
        const currentPath = window.location.pathname + window.location.hash;

        // 如果URL包含login相关路径
        if (currentPath.includes('login') || currentPath.includes('#/login')) {
            return true;
        }

        // 检查页面是否有登录表单的特征元素
        const hasPasswordInput = document.querySelector('input[type="password"]');
        const hasLoginButton = document.querySelector('button[type="submit"]') ||
                              document.querySelector('.ant-btn-primary');
        const hasLoginForm = document.querySelector('.ant-form') &&
                           document.querySelectorAll('.ant-form-item').length <= 3; // 登录表单通常只有2-3个字段

        // 检查是否有用户菜单或导航（已登录状态的标志）
        const hasUserMenu = document.querySelector('.ant-dropdown-trigger') ||
                           document.querySelector('.ant-menu') ||
                           document.querySelector('[class*="header"]') ||
                           document.querySelector('[class*="sidebar"]') ||
                           document.querySelector('[class*="nav"]');

        // 如果有密码输入框、登录按钮、简单表单，且没有用户菜单，则认为是登录页面
        return hasPasswordInput && hasLoginButton && hasLoginForm && !hasUserMenu;
    }

    // 在管理员登录表单中添加OAuth按钮
    function addOAuthToAdminForm() {
        // 首先检查是否在登录页面
        if (!isLoginPage()) {
            console.log('Admin OAuth: Not on login page, skipping OAuth buttons');
            return false;
        }

        // 检查是否已经添加了OAuth按钮
        if (document.querySelector('.admin-oauth-container')) {
            console.log('Admin OAuth: OAuth buttons already exist');
            return;
        }

        // 调试页面结构
        debugPageStructure();

        // V2Board管理员登录页面的特殊结构选择器
        const formSelectors = [
            '.block-content.block-content-full',  // V2Board管理员登录的主容器
            '.block-content',                     // V2Board容器的备选
            '.ant-form',                          // Ant Design表单
            'form[class*="login"]',               // 包含login的表单
            '[class*="login-form"]',              // 登录表单类
            'form',                               // 标准表单
            '.login-container form',              // 登录容器内的表单
            '[data-testid="login-form"]'          // 测试ID表单
        ];

        for (const selector of formSelectors) {
            const formElement = document.querySelector(selector);
            if (formElement) {
                console.log('Admin OAuth: Found login form with selector:', selector);
                console.log('Admin OAuth: Form element:', formElement);

                // 再次确认这是登录表单（双重检查）
                const hasPasswordField = formElement.querySelector('input[type="password"]');
                if (!hasPasswordField) {
                    console.log('Admin OAuth: Form does not have password field, skipping');
                    continue;
                }

                const oauthContainer = createOAuthButtons();
                if (oauthContainer) {
                    // 对于V2Board的.block-content结构，找到最后一个.form-group后插入
                    if (selector.includes('block-content')) {
                        const formGroups = formElement.querySelectorAll('.form-group');
                        console.log('Admin OAuth: Found form groups:', formGroups.length);
                        const lastFormGroup = formGroups[formGroups.length - 1];

                        if (lastFormGroup) {
                            console.log('Admin OAuth: Inserting after last form group:', lastFormGroup);
                            lastFormGroup.insertAdjacentElement('afterend', oauthContainer);
                        } else {
                            console.log('Admin OAuth: No form groups found, appending to container');
                            formElement.appendChild(oauthContainer);
                        }
                    } else {
                        // 对于其他表单结构，直接追加
                        formElement.appendChild(oauthContainer);
                    }

                    console.log('Admin OAuth: OAuth buttons added to admin login form');
                    return true;
                }
            } else {
                console.log('Admin OAuth: No element found for selector:', selector);
            }
        }

        console.log('Admin OAuth: No suitable login form found');
        return false;
    }

    // 使用MutationObserver监控DOM变化
    function setupDOMObserver() {
        const observer = new MutationObserver((mutations) => {
            let shouldCheck = false;

            // 只在可能是登录页面时才检查
            if (!isLoginPage()) {
                return;
            }

            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // 检查是否有新的表单元素被添加
                    for (const node of mutation.addedNodes) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.matches && (
                                node.matches('.ant-form') ||
                                node.matches('form') ||
                                node.matches('.block-content') ||
                                node.matches('.block-content-full') ||
                                node.querySelector('.ant-form') ||
                                node.querySelector('form') ||
                                node.querySelector('.block-content') ||
                                node.querySelector('.form-group')
                            )) {
                                shouldCheck = true;
                                break;
                            }
                        }
                    }
                }
            });

            if (shouldCheck) {
                console.log('Admin OAuth: DOM changed, checking for login form');
                setTimeout(addOAuthToAdminForm, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('Admin OAuth: DOM observer set up');
        return observer;
    }

    // 处理OAuth回调（成功和错误）
    function handleOAuthCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const hashParams = new URLSearchParams(window.location.hash.substring(window.location.hash.indexOf('?') + 1));

        // 检查OAuth错误参数
        const oauthError = urlParams.get('oauth_error') || hashParams.get('oauth_error');
        if (oauthError) {
            console.log('Admin OAuth: Processing OAuth error callback');

            // 显示错误消息
            const errorMessage = decodeURIComponent(oauthError);
            alert('OAuth登录失败: ' + errorMessage);

            // 清理URL参数
            const cleanUrl = window.location.pathname + window.location.hash.split('?')[0];
            window.history.replaceState({}, document.title, cleanUrl);

            return true;
        }

        // 检查OAuth成功参数
        const oauthSuccess = urlParams.get('oauth_success') || hashParams.get('oauth_success');
        const authData = urlParams.get('auth_data') || hashParams.get('auth_data');
        const token = urlParams.get('token') || hashParams.get('token');

        if (oauthSuccess === '1' && authData && token) {
            console.log('Admin OAuth: Processing OAuth success callback');

            try {
                // 解码认证数据
                const decodedAuthData = decodeURIComponent(authData);
                const decodedToken = decodeURIComponent(token);

                // 存储认证数据到localStorage
                localStorage.setItem('authorization', decodedAuthData);
                localStorage.setItem('app_token', decodedToken);

                console.log('Admin OAuth: Auth data stored successfully');

                // 清理URL参数
                const cleanUrl = window.location.pathname + window.location.hash.split('?')[0];
                window.history.replaceState({}, document.title, cleanUrl);

                // 显示成功消息
                console.log('Admin OAuth: Login successful, reloading page...');

                // 重新加载页面以应用认证状态
                setTimeout(() => {
                    window.location.reload();
                }, 500);

                return true;
            } catch (error) {
                console.error('Admin OAuth: Error processing OAuth callback:', error);
                alert('OAuth登录处理失败，请重试');
            }
        }

        return false;
    }

    // 初始化OAuth集成
    function initAdminOAuth() {
        console.log('Admin OAuth: Initializing...');

        // 防止重复初始化
        if (window.adminOAuthInitialized) {
            console.log('Admin OAuth: Already initialized');
            return;
        }
        window.adminOAuthInitialized = true;

        // 首先处理OAuth回调
        if (handleOAuthCallback()) {
            return; // 如果正在处理OAuth回调，不需要继续初始化
        }

        // 检查是否在登录页面
        if (!isLoginPage()) {
            console.log('Admin OAuth: Not on login page, skipping initialization');
            return;
        }

        // 立即尝试添加OAuth按钮
        if (!addOAuthToAdminForm()) {
            // 如果立即添加失败，等待DOM加载
            waitForElement('.block-content.block-content-full, .block-content, .ant-form, form', () => {
                addOAuthToAdminForm();
            });
        }

        // 设置DOM观察器以处理动态加载的内容
        setupDOMObserver();
    }

    // 清理OAuth按钮（当不在登录页面时）
    function cleanupOAuthButtons() {
        if (!isLoginPage()) {
            const existingContainers = document.querySelectorAll('.admin-oauth-container');
            if (existingContainers.length > 0) {
                console.log('Admin OAuth: Cleaning up OAuth buttons from non-login page');
                existingContainers.forEach(container => container.remove());
            }
        }
    }

    // 监听路由变化和页面变化
    function setupRouteMonitoring() {
        // 监听 hash 变化（SPA 路由）
        window.addEventListener('hashchange', () => {
            setTimeout(() => {
                cleanupOAuthButtons();
                if (isLoginPage()) {
                    addOAuthToAdminForm();
                }
            }, 100);
        });

        // 监听 popstate 事件
        window.addEventListener('popstate', () => {
            setTimeout(() => {
                cleanupOAuthButtons();
                if (isLoginPage()) {
                    addOAuthToAdminForm();
                }
            }, 100);
        });

        // 定期检查并清理（防止遗漏）
        setInterval(() => {
            cleanupOAuthButtons();
        }, 2000);
    }

    // 当DOM准备就绪时初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initAdminOAuth();
            setupRouteMonitoring();
        });
    } else {
        // DOM已经加载完成
        setTimeout(() => {
            initAdminOAuth();
            setupRouteMonitoring();
        }, 100);
    }

    // 也在window load事件时尝试初始化（以防万一）
    window.addEventListener('load', () => {
        setTimeout(() => {
            initAdminOAuth();
            setupRouteMonitoring();
        }, 500);
    });

    console.log('Admin OAuth: Script initialization complete');

    // 全局调试函数
    window.debugAdminOAuth = function() {
        console.log('Admin OAuth: Manual debug triggered');
        console.log('Admin OAuth: Is login page?', isLoginPage());
        debugPageStructure();

        // 清除现有的OAuth按钮
        const existing = document.querySelectorAll('.admin-oauth-container');
        existing.forEach(el => el.remove());

        // 只在登录页面尝试添加OAuth按钮
        if (isLoginPage()) {
            addOAuthToAdminForm();
        } else {
            console.log('Admin OAuth: Not on login page, skipping OAuth button addition');
        }
    };

    window.forceAddAdminOAuth = function() {
        console.log('Admin OAuth: Force add triggered');
        console.log('Admin OAuth: Is login page?', isLoginPage());

        // 清除现有的OAuth按钮
        const existing = document.querySelectorAll('.admin-oauth-container');
        existing.forEach(el => el.remove());

        // 只在登录页面强制添加
        if (isLoginPage()) {
            const blockContent = document.querySelector('.block-content');
            if (blockContent) {
                const oauthContainer = createOAuthButtons();
                if (oauthContainer) {
                    blockContent.appendChild(oauthContainer);
                    console.log('Admin OAuth: Force added OAuth buttons');
                }
            } else {
                console.log('Admin OAuth: No .block-content found for force add');
            }
        } else {
            console.log('Admin OAuth: Not on login page, skipping force add');
        }
    };

    // 全局清理函数
    window.cleanupAdminOAuth = function() {
        console.log('Admin OAuth: Manual cleanup triggered');
        const existing = document.querySelectorAll('.admin-oauth-container');
        existing.forEach(el => el.remove());
        console.log('Admin OAuth: Removed', existing.length, 'OAuth containers');
    };
})();
