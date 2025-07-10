// 用户头像显示功能
(function() {
    'use strict';

    console.log('User Avatar: Script loaded');

    // 等待元素出现的辅助函数
    function waitForElement(selector, callback, maxAttempts = 50) {
        let attempts = 0;
        const interval = setInterval(() => {
            const element = document.querySelector(selector);
            attempts++;

            if (element) {
                clearInterval(interval);
                console.log('User Avatar: Found element with selector:', selector);
                callback(element);
            } else if (attempts >= maxAttempts) {
                clearInterval(interval);
                console.log('User Avatar: Element not found after maximum attempts:', selector);
            }
        }, 200);
    }

    // 检查是否在用户管理页面
    function isUserManagementPage() {
        const url = window.location.href;
        return url.includes('/user') || url.includes('#/user') || 
               document.querySelector('.ant-table-tbody') !== null;
    }

    // 创建头像元素
    function createAvatarElement(avatarUrl, displayName, oauthName, email, isOAuthUser = false) {
        const avatarContainer = document.createElement('div');
        avatarContainer.className = 'user-avatar-container';
        avatarContainer.style.cssText = `
            display: flex;
            align-items: center;
            gap: 8px;
        `;

        // 为OAuth用户添加特殊标识
        if (isOAuthUser) {
            avatarContainer.setAttribute('data-oauth', 'true');
        }

        // 确定显示的用户名：优先display_name，然后oauth_name，最后email
        const userName = displayName || oauthName || email;

        const avatar = document.createElement('img');
        avatar.className = 'user-avatar';
        avatar.src = avatarUrl;
        avatar.alt = userName;
        avatar.style.cssText = `
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #d9d9d9;
            flex-shrink: 0;
        `;

        // 头像加载失败时的处理
        avatar.onerror = function() {
            this.src = 'https://cravatar.cn/avatar/' + btoa(email).replace(/[^a-zA-Z0-9]/g, '') + '?s=32&d=identicon';
        };

        const textContainer = document.createElement('div');
        textContainer.className = 'user-text-container';
        textContainer.style.cssText = `
            display: flex;
            flex-direction: column;
            min-width: 0;
        `;

        const displayNameEl = document.createElement('div');
        displayNameEl.className = 'user-display-name';
        displayNameEl.textContent = userName;
        displayNameEl.style.cssText = `
            font-weight: 500;
            color: #262626;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        `;

        const emailEl = document.createElement('div');
        emailEl.className = 'user-email';
        emailEl.textContent = email;
        emailEl.style.cssText = `
            font-size: 12px;
            color: #8c8c8c;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        `;

        textContainer.appendChild(displayNameEl);
        // 只有当用户名不是邮箱时才显示邮箱
        if (userName && userName !== email) {
            textContainer.appendChild(emailEl);
        }

        avatarContainer.appendChild(avatar);
        avatarContainer.appendChild(textContainer);

        return avatarContainer;
    }

    // 处理表格行数据
    function processTableRow(row, userData) {
        if (!userData || row.dataset.avatarProcessed) {
            return;
        }

        // 查找邮箱列
        const emailCell = row.querySelector('td:nth-child(2)'); // 假设邮箱在第二列
        if (!emailCell) {
            return;
        }

        const email = emailCell.textContent.trim();
        const matchedUser = userData.find(user => user.email === email);
        
        if (matchedUser && matchedUser.avatar_url) {
            // 替换邮箱列的内容为头像+信息
            emailCell.innerHTML = '';
            const avatarElement = createAvatarElement(
                matchedUser.avatar_url,
                matchedUser.display_name,
                matchedUser.oauth_name,
                matchedUser.email,
                matchedUser.is_oauth_user
            );
            emailCell.appendChild(avatarElement);

            // 标记已处理
            row.dataset.avatarProcessed = 'true';
            console.log('User Avatar: Processed row for user:', email);
        }
    }

    // 监听表格数据变化
    function observeTableChanges() {
        const observer = new MutationObserver((mutations) => {
            if (!isUserManagementPage()) {
                return;
            }

            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // 检查是否是表格行
                            if (node.tagName === 'TR' && node.classList.contains('ant-table-row')) {
                                // 延迟处理，等待数据加载
                                setTimeout(() => {
                                    fetchUserDataAndProcess([node]);
                                }, 100);
                            }
                            // 检查是否包含表格行
                            const rows = node.querySelectorAll('tr.ant-table-row');
                            if (rows.length > 0) {
                                setTimeout(() => {
                                    fetchUserDataAndProcess(Array.from(rows));
                                }, 100);
                            }
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('User Avatar: Table observer started');
    }

    // 存储用户数据
    let cachedUserData = [];

    // 拦截fetch请求以获取用户数据
    function interceptFetch() {
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).then(response => {
                // 检查是否是用户列表API请求
                if (args[0] && args[0].includes && args[0].includes('/admin/user/fetch')) {
                    response.clone().json().then(data => {
                        if (data && data.data && Array.isArray(data.data)) {
                            cachedUserData = data.data;
                            console.log('User Avatar: Cached user data:', cachedUserData.length, 'users');

                            // 延迟处理表格，确保DOM已更新
                            setTimeout(() => {
                                processExistingTable();
                            }, 200);
                        }
                    }).catch(err => {
                        console.log('User Avatar: Failed to parse user data:', err);
                    });
                }
                return response;
            });
        };
    }

    // 处理现有表格
    function processExistingTable() {
        const rows = document.querySelectorAll('.ant-table-tbody tr.ant-table-row');
        if (rows.length > 0) {
            console.log('User Avatar: Processing', rows.length, 'existing rows');
            fetchUserDataAndProcess(Array.from(rows));
        }
    }

    // 获取用户数据并处理表格
    function fetchUserDataAndProcess(rows) {
        rows.forEach(row => {
            if (row.dataset.avatarProcessed) {
                return;
            }

            const cells = row.querySelectorAll('td');
            if (cells.length < 2) {
                return;
            }

            const emailCell = cells[1]; // 假设邮箱在第二列
            const email = emailCell.textContent.trim();

            if (email && email.includes('@')) {
                // 查找缓存的用户数据
                const userData = cachedUserData.find(user => user.email === email);

                let avatarUrl, displayName, oauthName, isOAuthUser = false;
                if (userData) {
                    avatarUrl = userData.avatar_url || ('https://cravatar.cn/avatar/' + btoa(email).replace(/[^a-zA-Z0-9]/g, '').substring(0, 32) + '?s=32&d=identicon');
                    displayName = userData.display_name;
                    oauthName = userData.oauth_name;
                    isOAuthUser = userData.is_oauth_user || false;
                } else {
                    // 使用默认头像
                    const emailHash = btoa(email).replace(/[^a-zA-Z0-9]/g, '').substring(0, 32);
                    avatarUrl = 'https://cravatar.cn/avatar/' + emailHash + '?s=32&d=identicon';
                    displayName = null;
                    oauthName = null;
                    isOAuthUser = false;
                }

                emailCell.innerHTML = '';
                const avatarElement = createAvatarElement(avatarUrl, displayName, oauthName, email, isOAuthUser);
                emailCell.appendChild(avatarElement);

                row.dataset.avatarProcessed = 'true';
                console.log('User Avatar: Processed row for user:', email, userData ? '(with user data)' : '(default)',
                           displayName ? `display_name: ${displayName}` : '',
                           oauthName ? `oauth_name: ${oauthName}` : '',
                           isOAuthUser ? '(OAuth user)' : '');
            }
        });
    }

    // 初始化头像显示功能
    function initUserAvatars() {
        console.log('User Avatar: Initializing...');

        // 防止重复初始化
        if (window.userAvatarInitialized) {
            console.log('User Avatar: Already initialized');
            return;
        }
        window.userAvatarInitialized = true;

        // 拦截fetch请求
        interceptFetch();

        // 等待表格加载
        waitForElement('.ant-table-tbody', (tbody) => {
            console.log('User Avatar: Table found, processing existing rows');
            const rows = tbody.querySelectorAll('tr.ant-table-row');
            if (rows.length > 0) {
                fetchUserDataAndProcess(Array.from(rows));
            }
        });

        // 开始监听表格变化
        observeTableChanges();
    }

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserAvatars);
    } else {
        initUserAvatars();
    }

    // 路由变化时重新初始化
    let lastUrl = location.href;
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            setTimeout(() => {
                if (isUserManagementPage()) {
                    console.log('User Avatar: Route changed to user management page');
                    initUserAvatars();
                }
            }, 500);
        }
    }).observe(document, { subtree: true, childList: true });

    // 暴露全局函数用于调试
    window.forceProcessUserAvatars = function() {
        console.log('User Avatar: Force processing triggered');
        const rows = document.querySelectorAll('.ant-table-tbody tr.ant-table-row');
        if (rows.length > 0) {
            // 清除已处理标记
            rows.forEach(row => {
                delete row.dataset.avatarProcessed;
            });
            fetchUserDataAndProcess(Array.from(rows));
        } else {
            console.log('User Avatar: No table rows found');
        }
    };

})();
