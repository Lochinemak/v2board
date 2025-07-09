# V2Board OAuth 接入指南

本指南将帮助你为 V2Board 系统接入第三方 OAuth 认证功能。

## 1. 安装依赖

首先安装 Laravel Socialite 包：

```bash
composer require laravel/socialite
```

## 2. 运行数据库迁移

执行数据库迁移以添加 OAuth 相关字段：

```bash
php artisan migrate
```

## 3. 配置 OAuth 服务提供商

### 3.1 GitHub OAuth 配置

1. 访问 [GitHub Developer Settings](https://github.com/settings/applications/new)
2. 创建新的 OAuth App，填写以下信息：
   - **Application name**: 你的应用名称
   - **Homepage URL**: 你的网站主页地址
   - **Application description**: 应用描述
   - **Authorization callback URL**: `https://your-domain.com/api/v1/passport/oauth/github/callback`

3. 获取 Client ID 和 Client Secret，添加到 `.env` 文件：

```env
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
```

### 3.2 Google OAuth 配置

1. 访问 [Google Cloud Console](https://console.developers.google.com/apis/credentials)
2. 创建新的 OAuth 2.0 客户端 ID：
   - **应用类型**: Web 应用
   - **名称**: 你的应用名称
   - **已获授权的重定向 URI**: `https://your-domain.com/api/v1/passport/oauth/google/callback`

3. 获取客户端 ID 和客户端密钥，添加到 `.env` 文件：

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
```

### 3.3 自定义 OAuth 提供商配置

如果你使用其他 OAuth 提供商，需要：

1. 在该提供商的开发者控制台创建应用
2. 设置回调地址为：`https://your-domain.com/api/v1/passport/oauth/oauth_provider/callback`
3. 配置环境变量：

```env
OAUTH_CLIENT_ID=your_oauth_client_id
OAUTH_CLIENT_SECRET=your_oauth_client_secret
OAUTH_PROVIDER_NAME="Your OAuth Provider Name"
```

4. 如果使用自定义提供商，需要扩展 Socialite 驱动程序。

## 4. 配置应用 URL

确保在 `.env` 文件中正确设置了应用 URL：

```env
APP_URL=https://your-domain.com
```

## 5. 前端集成

### 5.1 在登录页面添加 OAuth 按钮

在你的登录页面模板中添加 OAuth 按钮容器：

```html
<!-- 在登录表单后添加 -->
<div id="oauth-login-container"></div>

<!-- 引入 OAuth 登录脚本 -->
<script src="/theme/default/assets/oauth-login.js"></script>
```

### 5.2 自定义样式

你可以通过修改 `oauth-login.js` 文件中的 CSS 样式来自定义 OAuth 按钮的外观。

## 6. API 端点

系统提供以下 OAuth 相关的 API 端点：

- `GET /api/v1/passport/oauth/providers` - 获取可用的 OAuth 提供商
- `GET /api/v1/passport/oauth/{provider}/redirect` - 重定向到 OAuth 提供商
- `GET /api/v1/passport/oauth/{provider}/callback` - OAuth 回调处理

## 7. 测试 OAuth 功能

### 7.1 测试步骤

1. 确保所有配置正确
2. 访问登录页面
3. 点击 OAuth 登录按钮
4. 完成第三方认证
5. 验证是否成功登录到系统

### 7.2 调试

如果遇到问题，可以：

1. 检查 Laravel 日志文件 `storage/logs/laravel.log`
2. 确认回调 URL 配置正确
3. 验证 Client ID 和 Client Secret
4. 检查网络连接和防火墙设置

## 8. 安全注意事项

1. **HTTPS**: 生产环境必须使用 HTTPS
2. **Client Secret**: 妥善保管 Client Secret，不要泄露
3. **回调 URL**: 确保回调 URL 准确无误
4. **用户数据**: 遵守相关隐私法规处理用户数据

## 9. 高级配置

### 9.1 自定义用户创建逻辑

你可以修改 `app/Services/OAuthService.php` 中的 `createUserFromOAuth` 方法来自定义新用户的创建逻辑。

### 9.2 添加更多 OAuth 提供商

要添加更多 OAuth 提供商：

1. 在 `config/services.php` 中添加配置
2. 更新 `OAuthService.php` 中的 `getSupportedProviders` 方法
3. 如需要，创建自定义 Socialite 驱动程序

### 9.3 用户账号绑定

系统支持将 OAuth 账号绑定到现有用户账号。如果用户使用相同邮箱，系统会自动绑定。

## 10. 故障排除

### 常见问题

1. **"Unsupported OAuth provider"**: 检查提供商配置和路由
2. **"OAuth callback failed"**: 检查回调 URL 和网络连接
3. **"Email already exists"**: 用户邮箱已存在，会自动绑定账号
4. **"Your account has been suspended"**: 用户账号被禁用

### 日志查看

```bash
tail -f storage/logs/laravel.log
```

## 支持

如果遇到问题，请检查：
1. Laravel 版本兼容性
2. PHP 版本要求
3. 扩展依赖（如 curl、openssl）
4. 服务器配置

---

完成以上配置后，你的 V2Board 系统就支持第三方 OAuth 登录了！
