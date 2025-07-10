# V2Board 模板配置修复指南

## 问题描述
管理员页面正常，但普通用户模板渲染失败，主要原因是主题配置不匹配。

## 修复步骤

### 1. 清除现有缓存
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 2. 创建 standard 主题（如果不存在）
```bash
# 复制 default 主题到 standard
cp -r public/templates/default public/templates/standard

# 修改主题配置文件
# 编辑 public/templates/standard/config.json
# 将 "name": "default" 改为 "name": "standard"
# 将 "description": "默认主题" 改为 "description": "标准主题"
```

### 3. 更新 V2Board 配置
确保 `config/v2board.php` 包含以下配置：
```php
<?php
return [
    // 站点设置
    'app_name' => 'CloudPanel',
    'app_description' => 'Secure Network Service',
    'app_url' => null,
    
    // 订阅设置
    'subscribe_url' => null,
    'subscribe_path' => '/my-custom-subscribe',
    
    // 前端主题设置
    'frontend_theme' => 'standard',
    'frontend_theme_sidebar' => 'light',
    'frontend_theme_header' => 'dark',
    'frontend_theme_color' => 'default',
    'frontend_background_url' => '',
    
    // 其他配置...
];
```

### 4. 初始化主题配置
```bash
# 使用 artisan 命令初始化主题
php artisan tinker --execute="
\$themeService = new \App\Services\ThemeService('standard');
\$themeService->init();
echo 'Standard theme initialized successfully';
"
```

### 5. 重新缓存配置
```bash
php artisan config:cache
```

### 6. 验证修复
```bash
# 生成访问链接
php artisan access:url --domain=yourdomain.com

# 测试访问
curl -s "http://yourdomain.com/?access=YOUR_ACCESS_KEY" | head -10
```

## 验证清单

- [ ] `public/templates/standard` 目录存在
- [ ] `config/templates/standard.php` 文件存在
- [ ] `config/v2board.php` 包含正确的主题配置
- [ ] 普通用户页面能正常访问
- [ ] 管理后台页面能正常访问
- [ ] 伪装首页正常显示

## 常见问题

### Q: 仍然显示模板不存在错误
A: 检查文件权限，确保 web 服务器有读取权限：
```bash
chmod -R 755 public/templates/
chmod -R 755 config/templates/
```

### Q: 配置缓存后仍然有问题
A: 完全清除所有缓存：
```bash
php artisan optimize:clear
php artisan config:cache
```

### Q: 主题样式不正确
A: 检查静态资源路径，确保 CSS/JS 文件存在：
```bash
ls -la public/templates/standard/assets/
```

## 技术说明

V2Board 使用 Laravel 的视图命名空间机制来加载主题模板：
- 在 `AppServiceProvider` 中注册了 `theme` 命名空间指向 `public/templates`
- 模板通过 `theme::主题名.模板名` 的方式加载
- 主题配置存储在 `config/templates/主题名.php` 中
