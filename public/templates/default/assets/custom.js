// OAuth登录按钮集成
(function() {
    'use strict';

    console.log('OAuth integration: Script loaded');
    console.log('OAuth integration: Settings available:', !!window.settings);
    console.log('OAuth integration: OAuth providers:', window.settings?.oauth_providers);

    // 等待页面加载完成
    function waitForElement(selector, callback, maxAttempts = 50) {
        let attempts = 0;
        const interval = setInterval(() => {
            const element = document.querySelector(selector);
            attempts++;

            if (element) {
                clearInterval(interval);
                console.log('OAuth integration: Found element with selector:', selector);
                callback(element);
            } else if (attempts >= maxAttempts) {
                clearInterval(interval);
                console.log('OAuth integration: Element not found after maximum attempts:', selector);
            }
        }, 200);
    }
    
    // 创建OAuth登录按钮
    function createOAuthButtons() {
        const oauthProviders = window.settings?.oauth_providers;
        if (!oauthProviders) {
            console.log('OAuth integration: No OAuth providers configured');
            return null;
        }

        const container = document.createElement('div');
        container.className = 'oauth-login-container custom-oauth-wrapper';

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
            button.href = provider.url;
            button.className = `oauth-btn oauth-btn-${providerKey}`;

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

            // 添加点击事件
            button.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = provider.url;
            });

            buttonsContainer.appendChild(button);
        });

        container.appendChild(buttonsContainer);

        // 应用简洁的admin风格样式
        setTimeout(() => {
            const oauthBtns = container.querySelectorAll('.oauth-btn');
            oauthBtns.forEach(btn => {
                // 基础样式
                btn.style.cssText = `
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 8px;
                    padding: 10px 16px !important;
                    border: 1px solid #d9d9d9 !important;
                    border-radius: 6px !important;
                    background: #ffffff !important;
                    color: #595959 !important;
                    text-decoration: none !important;
                    font-size: 14px !important;
                    font-weight: 400 !important;
                    cursor: pointer;
                    transition: all 0.2s ease !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                    min-height: 40px !important;
                    line-height: normal !important;
                `;

                // Linux.do特殊样式
                if (btn.classList.contains('oauth-btn-linuxdo')) {
                    btn.style.borderColor = '#f39c12';
                    btn.style.color = '#f39c12';
                }

                // SVG图标样式
                const svg = btn.querySelector('.oauth-icon');
                if (svg) {
                    svg.style.cssText = `
                        width: 16px !important;
                        height: 16px !important;
                        flex-shrink: 0 !important;
                        opacity: 0.8;
                    `;
                }

                // Font Awesome图标样式
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.style.cssText = `
                        font-size: 16px;
                        opacity: 0.8;
                    `;
                }
            });
        }, 100);

        return container;
    }
    
    // 在登录表单中添加OAuth按钮
    function addOAuthToLoginForm() {
        console.log('OAuth integration: Attempting to add OAuth buttons to login form');

        // 首先检查是否已经有OAuth按钮了
        if (document.querySelector('.oauth-login-container')) {
            console.log('OAuth integration: OAuth buttons already exist globally');
            return;
        }

        // 查找登录表单容器，避免选择按钮内部
        const loginFormSelectors = [
            '.block-content.block-content-full',  // V2Board特有的登录表单容器
            '.block-content',                     // V2Board容器的备选
            'form',                               // 标准表单
            '.login-form',                        // 通用登录表单
            '.ant-form',                          // Ant Design表单
            '[class*="login"][class*="form"]',    // 包含login和form的类
            '.card-body',                         // Bootstrap卡片
            '.ant-card-body'                      // Ant Design卡片
        ];

        for (const selector of loginFormSelectors) {
            console.log('OAuth integration: Trying selector:', selector);
            const formElement = document.querySelector(selector);

            if (formElement && !formElement.closest('button')) { // 确保不是按钮内部的元素
                console.log('OAuth integration: Found form element:', formElement);

                // 再次检查是否已经添加了OAuth按钮
                if (formElement.querySelector('.oauth-login-container')) {
                    console.log('OAuth integration: OAuth buttons already exist in form');
                    return;
                }

                const oauthContainer = createOAuthButtons();

                if (oauthContainer) {
                    // 查找最佳插入位置 - 专门处理V2Board结构
                    let insertionParent = null;
                    let insertionPoint = null;

                    // V2Board特殊处理：查找最后一个.form-group
                    const formGroups = formElement.querySelectorAll('.form-group');
                    const lastFormGroup = formGroups[formGroups.length - 1];

                    if (lastFormGroup && lastFormGroup.parentNode === formElement) {
                        // 在最后一个form-group后插入
                        insertionParent = formElement;
                        insertionPoint = lastFormGroup.nextSibling;
                        console.log('OAuth integration: Will insert after last V2Board form group');
                    } else {
                        // 备选方案：查找Ant Design表单项
                        const antFormItems = formElement.querySelectorAll('.ant-form-item');
                        const lastAntFormItem = antFormItems[antFormItems.length - 1];

                        if (lastAntFormItem && lastAntFormItem.parentNode === formElement) {
                            insertionParent = formElement;
                            insertionPoint = lastAntFormItem.nextSibling;
                            console.log('OAuth integration: Will insert after last Ant form item');
                        } else {
                            // 最后的备选方案：直接添加到表单末尾
                            insertionParent = formElement;
                            insertionPoint = null;
                            console.log('OAuth integration: Will append to form end');
                        }
                    }

                    // 执行插入
                    if (insertionPoint) {
                        insertionParent.insertBefore(oauthContainer, insertionPoint);
                    } else {
                        insertionParent.appendChild(oauthContainer);
                    }

                    console.log('OAuth integration: OAuth buttons added successfully to:', insertionParent);
                    console.log('OAuth integration: Insertion point:', insertionPoint);
                    return; // 成功添加后立即返回
                } else {
                    console.log('OAuth integration: Failed to create OAuth container');
                }
            }
        }
    }
    
    // 在管理员登录表单中添加OAuth按钮
    function addOAuthToAdminLoginForm() {
        // 首先检查是否已经有OAuth按钮了
        if (document.querySelector('.oauth-login-container')) {
            console.log('OAuth integration: OAuth buttons already exist globally (admin)');
            return;
        }

        // 管理员页面的登录表单选择器
        const adminLoginSelectors = [
            '.ant-form',
            'form',
            '[class*="login"]'
        ];

        for (const selector of adminLoginSelectors) {
            const formElement = document.querySelector(selector);

            if (formElement) {
                // 检查是否已经添加了OAuth按钮
                if (formElement.querySelector('.oauth-login-container')) {
                    console.log('OAuth integration: OAuth buttons already exist in admin form');
                    return;
                }

                const oauthContainer = createOAuthButtons();
                if (oauthContainer) {
                    formElement.appendChild(oauthContainer);
                    console.log('OAuth integration: OAuth buttons added to admin login form');
                    return; // 成功添加后立即返回
                }
            }
        }
    }
    
    // 检测当前页面类型并添加相应的OAuth按钮
    function initOAuthIntegration() {
        // 防止重复初始化
        if (window.oauthIntegrationInitialized) {
            console.log('OAuth integration: Already initialized');
            return;
        }
        window.oauthIntegrationInitialized = true;

        // 设置DOM监控器
        setupOAuthMonitor();

        // 等待React/Vue应用加载完成
        setTimeout(() => {
            const currentPath = window.location.hash || window.location.pathname;

            // 检查是否是登录页面
            if (currentPath.includes('login') || currentPath.includes('#/login')) {
                console.log('OAuth integration: Detected login page');
                addOAuthToLoginForm();
                addOAuthToAdminLoginForm();
            }

            // 监听路由变化
            const originalPushState = history.pushState;
            const originalReplaceState = history.replaceState;

            function handleRouteChange() {
                setTimeout(() => {
                    const newPath = window.location.hash || window.location.pathname;
                    if (newPath.includes('login')) {
                        // 清除现有的OAuth按钮
                        const existingButtons = document.querySelectorAll('.oauth-login-container');
                        existingButtons.forEach(btn => btn.remove());

                        addOAuthToLoginForm();
                        addOAuthToAdminLoginForm();
                    }
                }, 500);
            }

            history.pushState = function() {
                originalPushState.apply(history, arguments);
                handleRouteChange();
            };

            history.replaceState = function() {
                originalReplaceState.apply(history, arguments);
                handleRouteChange();
            };

            window.addEventListener('popstate', handleRouteChange);
            window.addEventListener('hashchange', handleRouteChange);

        }, 1000);
    }
    
    // 专门检测V2Board登录表单的函数
    window.detectV2BoardLoginForm = function() {
        console.log('OAuth integration: Detecting V2Board login form structure');

        const blockContent = document.querySelector('.block-content.block-content-full');
        if (blockContent) {
            console.log('OAuth integration: Found V2Board block-content container');

            const formGroups = blockContent.querySelectorAll('.form-group');
            console.log('OAuth integration: Found form groups:', formGroups.length);

            formGroups.forEach((group, index) => {
                console.log(`OAuth integration: Form group ${index}:`, group);
                const button = group.querySelector('button[type="submit"]');
                if (button) {
                    console.log('OAuth integration: Found submit button in group:', group);
                }
            });

            const submitButton = blockContent.querySelector('button[type="submit"]');
            if (submitButton) {
                console.log('OAuth integration: Submit button parent chain:');
                let parent = submitButton.parentNode;
                let level = 0;
                while (parent && level < 5) {
                    console.log(`OAuth integration: Level ${level}:`, parent.tagName, parent.className);
                    parent = parent.parentNode;
                    level++;
                }
            }
        }

        return blockContent;
    };

    // 全局函数，用于手动测试
    window.addOAuthButtons = function() {
        console.log('OAuth integration: Manual OAuth button addition triggered');

        // 先检测表单结构
        detectV2BoardLoginForm();

        // 先清除现有的OAuth按钮
        const existingButtons = document.querySelectorAll('.oauth-login-container');
        existingButtons.forEach(btn => btn.remove());

        addOAuthToLoginForm();
        addOAuthToAdminLoginForm();
    };

    // 全局函数，用于清除OAuth按钮
    window.clearOAuthButtons = function() {
        const existingButtons = document.querySelectorAll('.oauth-login-container');
        existingButtons.forEach(btn => btn.remove());
        console.log('OAuth integration: Cleared', existingButtons.length, 'OAuth button containers');
    };

    // 全局函数，用于修复错误插入的OAuth按钮
    window.fixOAuthButtons = function() {
        console.log('OAuth integration: Fixing OAuth button positions');

        // 清除所有现有的OAuth按钮
        document.querySelectorAll('.oauth-login-container').forEach(container => {
            container.remove();
        });

        // 重置初始化标志
        window.oauthIntegrationInitialized = false;

        // 重新初始化
        setTimeout(() => {
            initOAuthIntegration();
        }, 100);
    };

    // 监控DOM变化，防止OAuth按钮被错误插入
    function setupOAuthMonitor() {
        let fixInProgress = false;

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('oauth-login-container')) {
                        // 检查是否被插入到按钮内部或其他错误位置
                        const wrongParents = node.closest('button, .btn, i, span, .btn-primary');
                        const correctParent = node.closest('.block-content, .ant-form, form');

                        if (wrongParents && !fixInProgress) {
                            console.log('OAuth integration: Detected OAuth container in wrong position:', wrongParents);
                            console.log('OAuth integration: Container parent chain:', node.parentNode);

                            fixInProgress = true;
                            // 延迟修复，避免无限循环
                            setTimeout(() => {
                                fixOAuthButtons();
                                fixInProgress = false;
                            }, 100);
                        } else if (correctParent) {
                            console.log('OAuth integration: OAuth container correctly positioned in:', correctParent);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('OAuth integration: DOM monitor setup complete');
    }

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOAuthIntegration);
    } else {
        initOAuthIntegration();
    }

    // 为了确保在SPA路由变化时也能工作，只添加一个延迟监听
    setTimeout(() => {
        if (!window.oauthIntegrationInitialized) {
            initOAuthIntegration();
        }
    }, 3000);

    // 1. 修复登录按钮cursor
    document.querySelectorAll('button, .btn, .btn-primary, input[type="submit"]').forEach(btn => {
        btn.style.cursor = 'pointer';
    });

    // 2. OAuth用户密码设置功能
    function initOAuthPasswordSetup() {
        // 检查是否在用户设置页面
        if (window.location.hash && window.location.hash.includes('profile')) {
            setTimeout(() => {
                addOAuthPasswordSetupUI();
            }, 2000);
        }

        // 监听路由变化
        const originalPushState = history.pushState;
        const originalReplaceState = history.replaceState;

        function handleRouteChange() {
            setTimeout(() => {
                const currentPath = window.location.hash || window.location.pathname;
                if (currentPath.includes('profile')) {
                    addOAuthPasswordSetupUI();
                }
            }, 1000);
        }

        history.pushState = function() {
            originalPushState.apply(history, arguments);
            handleRouteChange();
        };

        history.replaceState = function() {
            originalReplaceState.apply(history, arguments);
            handleRouteChange();
        };

        window.addEventListener('popstate', handleRouteChange);
    }

    // 添加OAuth用户密码设置UI
    function addOAuthPasswordSetupUI() {
        // 检查用户是否是OAuth用户
        const authToken = localStorage.getItem('authorization');
        if (!authToken) {
            console.log('OAuth integration: No authorization token found');
            return;
        }

        fetch('/api/v1/user/info', {
            method: 'GET',
            headers: {
                'Authorization': `${authToken}`,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.data && data.data.is_oauth_user) {
                // 是OAuth用户，添加特殊的密码设置界面
                addOAuthPasswordForm(data.data);
            }
        })
        .catch(error => {
            console.log('OAuth integration: Failed to check user info:', error);
        });
    }

    // 添加OAuth用户密码设置表单
    function addOAuthPasswordForm(userInfo) {
        // 查找密码修改表单
        const passwordForms = document.querySelectorAll('form');
        let passwordForm = null;

        for (let form of passwordForms) {
            const oldPasswordInput = form.querySelector('input[type="password"]');
            if (oldPasswordInput && oldPasswordInput.placeholder &&
                (oldPasswordInput.placeholder.includes('旧密码') ||
                 oldPasswordInput.placeholder.includes('old') ||
                 oldPasswordInput.placeholder.includes('当前密码'))) {
                passwordForm = form;
                break;
            }
        }

        if (passwordForm && !passwordForm.querySelector('.oauth-password-notice')) {
            // 添加OAuth用户提示
            const notice = document.createElement('div');
            notice.className = 'oauth-password-notice';
            notice.style.cssText = `
                background: #e6f7ff;
                border: 1px solid #91d5ff;
                border-radius: 6px;
                padding: 12px;
                margin-bottom: 16px;
                color: #0050b3;
                font-size: 14px;
            `;

            const providerName = userInfo.oauth_provider === 'linuxdo' ? 'Linux.Do' : userInfo.oauth_provider;
            notice.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <div>
                        <strong>OAuth账户密码设置</strong><br>
                        您通过 ${providerName} 登录，首次设置密码时无需输入旧密码。
                    </div>
                </div>
            `;

            passwordForm.insertBefore(notice, passwordForm.firstChild);

            // 修改旧密码输入框
            const oldPasswordInput = passwordForm.querySelector('input[type="password"]');
            if (oldPasswordInput) {
                oldPasswordInput.placeholder = '首次设置密码请留空或输入任意内容';

                // 添加特殊处理
                const originalSubmit = passwordForm.onsubmit;
                passwordForm.onsubmit = function(e) {
                    e.preventDefault();

                    const formData = new FormData(passwordForm);
                    const newPassword = formData.get('new_password') ||
                                      passwordForm.querySelector('input[name*="new"]').value ||
                                      passwordForm.querySelector('input[type="password"]:last-child').value;

                    // 使用特殊标识符作为旧密码
                    const requestData = {
                        old_password: 'OAUTH_USER_FIRST_PASSWORD_SETUP',
                        new_password: newPassword
                    };

                    const authToken = localStorage.getItem('authorization');
                    if (!authToken) {
                        alert('请先登录');
                        return;
                    }

                    fetch('/api/v1/user/changePassword', {
                        method: 'POST',
                        headers: {
                            'Authorization': `${authToken}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.data === true) {
                            alert('密码设置成功！请重新登录。');
                            // 清除认证信息，强制重新登录
                            localStorage.removeItem('authorization');
                            localStorage.removeItem('app_token');
                            window.location.href = '/#/login';
                        } else {
                            alert('密码设置失败：' + (data.message || '未知错误'));
                        }
                    })
                    .catch(error => {
                        alert('网络错误，请稍后重试');
                        console.error('Password setup error:', error);
                    });
                };
            }
        }
    }

    // 初始化OAuth密码设置功能
    initOAuthPasswordSetup();

})();
