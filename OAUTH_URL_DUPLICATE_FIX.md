# OAuth 回调 URL 重复参数修复指南

## 问题描述

OAuth 登录回调时出现重复的 `access` 参数，导致 URL 格式错误：

```
错误URL: https://pt.kktwo.me/?access=694daaea26e3269560f9727bb874cb87/?access=5b711701#/login?verify=...
正确URL: https://pt.kktwo.me/?access=5b711701#/login?verify=...
```

## 问题原因

1. **用户访问方式**：用户通过每日密钥访问页面 `?access=694daaea...`
2. **OAuth回调处理**：系统又添加了固定密钥 `?access=5b711701`
3. **URL构建问题**：Laravel 的 `url()` 函数可能保留当前查询参数，导致重复

## 根本原因分析

```
用户访问: https://pt.kktwo.me/?access=694daaea26e3269560f9727bb874cb87
         ↓ 点击OAuth登录
OAuth回调: 系统构建回调URL时又添加 /?access=5b711701
         ↓ URL拼接错误
最终URL: https://pt.kktwo.me/?access=694daaea.../?access=5b711701#/login
```

## 修复方案

### 1. 修改 OAuth 控制器 URL 构建逻辑

修改 `app/Http/Controllers/V1/Passport/OAuthController.php` 文件：

#### 成功回调 URL 构建修复

```php
// 原代码（第123-130行）
// Use app_url if configured, ensure proper URL construction
if (config('v2board.app_url')) {
    $baseUrl = rtrim(config('v2board.app_url'), '/');
    $fullUrl = $baseUrl . $redirectUrl;
} else {
    // Use Laravel's url() helper which handles base URL properly
    $fullUrl = url('/') . $redirectUrl;
}

// 修复后代码
// Use app_url if configured, ensure proper URL construction
if (config('v2board.app_url')) {
    $baseUrl = rtrim(config('v2board.app_url'), '/');
    $fullUrl = $baseUrl . $redirectUrl;
} else {
    // Build clean URL without preserving current query parameters
    $scheme = $request->isSecure() ? 'https' : 'http';
    $host = $request->getHost();
    $port = $request->getPort();
    
    $baseUrl = $scheme . '://' . $host;
    if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
        $baseUrl .= ':' . $port;
    }
    
    $fullUrl = $baseUrl . $redirectUrl;
}
```

#### 错误回调 URL 构建修复

```php
// 原代码（第185-192行）
// Use app_url if configured, ensure proper URL construction
if (config('v2board.app_url')) {
    $baseUrl = rtrim(config('v2board.app_url'), '/');
    $fullUrl = $baseUrl . $redirectUrl;
} else {
    // Use Laravel's url() helper which handles base URL properly
    $fullUrl = url('/') . $redirectUrl;
}

// 修复后代码
// Use app_url if configured, ensure proper URL construction
if (config('v2board.app_url')) {
    $baseUrl = rtrim(config('v2board.app_url'), '/');
    $fullUrl = $baseUrl . $redirectUrl;
} else {
    // Build clean URL without preserving current query parameters
    $scheme = request()->isSecure() ? 'https' : 'http';
    $host = request()->getHost();
    $port = request()->getPort();
    
    $baseUrl = $scheme . '://' . $host;
    if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
        $baseUrl .= ':' . $port;
    }
    
    $fullUrl = $baseUrl . $redirectUrl;
}
```

### 2. 修复原理

**问题根源**：
- Laravel 的 `url()` 函数可能会保留当前请求的查询参数
- 当用户通过 `?access=每日密钥` 访问页面时，OAuth 回调又添加 `?access=固定密钥`
- 导致 URL 格式错误：`/?access=xxx/?access=yyy`

**修复方案**：
- 手动构建基础 URL，不使用 `url()` 函数
- 确保生成干净的 URL，不保留当前查询参数
- 只使用 OAuth 回调时指定的固定密钥

## 部署步骤

### 1. 应用修复

```bash
# 1. 修改 OAuthController.php 文件（按上述代码修改）

# 2. 清除配置缓存
php artisan config:cache

# 3. 重启服务（如果使用进程管理器）
# systemctl restart php-fpm
# supervisorctl restart v2board
```

### 2. 验证修复

#### 测试不同访问场景

```bash
# 场景1：用户通过每日密钥访问后OAuth登录
# 访问: https://yourdomain.com/?access=每日密钥
# OAuth回调应该生成: https://yourdomain.com/?access=固定密钥#/login?verify=code

# 场景2：用户通过固定密钥访问后OAuth登录  
# 访问: https://yourdomain.com/?access=固定密钥
# OAuth回调应该生成: https://yourdomain.com/?access=固定密钥#/login?verify=code

# 场景3：用户直接访问首页后OAuth登录
# 访问: https://yourdomain.com/
# OAuth回调应该生成: https://yourdomain.com/?access=固定密钥#/login?verify=code
```

#### 检查 URL 格式

正确的 OAuth 回调 URL 格式：
```
https://yourdomain.com/?access=84c4bf5d#/login?verify=code&redirect=dashboard
```

错误的 URL 格式（修复前）：
```
https://yourdomain.com/?access=每日密钥/?access=固定密钥#/login?verify=code
```

### 3. 测试 OAuth 流程

1. **准备测试**：
   ```bash
   # 获取当前访问密钥
   php artisan access:url --domain=yourdomain.com
   ```

2. **测试步骤**：
   - 通过每日密钥访问：`https://yourdomain.com/?access=每日密钥`
   - 点击 OAuth 登录按钮
   - 完成第三方认证
   - 检查回调 URL 是否正确
   - 验证是否能正常登录

3. **验证结果**：
   - ✅ URL 格式正确，无重复参数
   - ✅ 能正常重定向到真实页面
   - ✅ OAuth 登录流程完整

## 技术细节

### URL 构建对比

#### 修复前（有问题）
```php
$fullUrl = url('/') . $redirectUrl;
// 可能生成: https://domain.com/?access=current_param/?access=new_param#/login
```

#### 修复后（正确）
```php
$scheme = $request->isSecure() ? 'https' : 'http';
$host = $request->getHost();
$baseUrl = $scheme . '://' . $host;
$fullUrl = $baseUrl . $redirectUrl;
// 生成: https://domain.com/?access=new_param#/login
```

### 访问密钥说明

- **每日密钥**：32位，每天变化，用于日常访问
- **固定密钥**：8位，永久有效，用于 OAuth 回调
- **OAuth 统一使用固定密钥**：确保回调 URL 始终有效

### 兼容性说明

- **前端无需修改**：JavaScript 正确解析 URL 参数
- **现有功能不受影响**：只修复 URL 构建逻辑
- **向后兼容**：支持所有访问方式

## 故障排除

### 问题：仍然出现重复参数
**解决**：
1. 检查 `config('v2board.app_url')` 配置
2. 确认代码修改是否正确应用
3. 清除配置缓存：`php artisan config:cache`

### 问题：OAuth 回调后显示伪装页面
**解决**：
1. 检查生成的 URL 是否包含 `access` 参数
2. 验证访问密钥是否正确
3. 检查反扫描中间件配置

### 问题：URL 格式仍然错误
**解决**：
1. 检查 Web 服务器配置（Nginx/Apache）
2. 确认 Laravel 路由配置
3. 检查是否有其他中间件影响 URL 构建

## 预防措施

1. **监控 OAuth URL**：定期检查 OAuth 回调 URL 格式
2. **自动化测试**：添加 OAuth 流程的自动化测试
3. **日志记录**：记录 OAuth 回调 URL 生成过程
4. **配置验证**：定期验证访问密钥配置

## 总结

这个修复解决了 OAuth 回调 URL 中重复 `access` 参数的问题，确保：

- ✅ URL 格式正确
- ✅ 无重复查询参数  
- ✅ OAuth 流程正常
- ✅ 兼容所有访问方式

修复的核心是避免使用 Laravel 的 `url()` 函数，手动构建干净的基础 URL，确保不会保留当前请求的查询参数。
