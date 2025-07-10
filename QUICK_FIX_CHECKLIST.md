# V2Board 快速修复清单

## 管理员页面404问题

### 🚨 关键步骤（必须执行）

```bash
# 1. 清除所有缓存
php artisan route:clear && php artisan config:clear

# 2. 重新生成访问链接
php artisan access:url --domain=yourdomain.com

# 3. 验证访问
curl -s "http://yourdomain.com/管理员路径" | head -5
```

### ✅ 检查清单

- [ ] 执行了 `php artisan route:clear && php artisan config:clear`
- [ ] 配置文件 `config/v2board.php` 包含 `secure_path` 配置
- [ ] 管理员路径配置与生成路径一致
- [ ] 静态资源文件存在于 `public/static/panel/`
- [ ] 视图文件 `resources/views/admin.blade.php` 存在

## 模板渲染失败问题

### 🚨 关键步骤

```bash
# 1. 清除缓存
php artisan config:clear && php artisan cache:clear && php artisan view:clear

# 2. 确保主题存在
ls -la public/templates/standard/

# 3. 重新缓存配置
php artisan config:cache
```

### ✅ 检查清单

- [ ] `public/templates/standard/` 目录存在
- [ ] `config/templates/standard.php` 配置文件存在
- [ ] `config/v2board.php` 中 `frontend_theme` 设置为 `standard`
- [ ] 执行了缓存清除和重建

## OAuth 回调问题

### 🚨 关键步骤

```bash
# 1. 确认访问密钥
php artisan access:url --domain=yourdomain.com

# 2. 测试回调URL格式
echo "OAuth回调应该包含: /?access=访问密钥#/login?verify=code"
```

### ✅ 检查清单

- [ ] OAuth 回调 URL 包含 `?access=` 参数
- [ ] 访问密钥与系统生成的一致
- [ ] 前端能正确解析 URL 参数

## 通用故障排除

### 缓存问题
```bash
# 清除所有缓存
php artisan optimize:clear

# 重新生成缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 权限问题
```bash
# 检查文件权限
chmod -R 755 public/
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 配置验证
```bash
# 验证配置一致性
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'Config secure_path: ' . config('v2board.secure_path') . PHP_EOL;
echo 'Generated path: ' . \App\Utils\AdminPathGenerator::generate() . PHP_EOL;
echo 'Current path: ' . \App\Utils\AdminPathGenerator::getCurrentPath() . PHP_EOL;
echo 'Frontend theme: ' . config('v2board.frontend_theme') . PHP_EOL;
"
```

## 常见错误及解决方案

### 错误：Route [xxx] not defined
**解决**：`php artisan route:clear && php artisan config:cache`

### 错误：View [xxx] not found
**解决**：`php artisan view:clear && php artisan config:cache`

### 错误：Class 'xxx' not found
**解决**：`composer dump-autoload && php artisan config:cache`

### 错误：SQLSTATE[42S02]: Base table or view not found
**解决**：检查数据库连接和表是否存在

## 部署后必执行命令

```bash
#!/bin/bash
# deploy.sh - 部署后执行脚本

echo "🚀 开始部署后配置..."

# 1. 清除所有缓存
echo "🧹 清除缓存..."
php artisan optimize:clear

# 2. 生成访问链接
echo "🔗 生成访问链接..."
php artisan access:url --domain=$1

# 3. 重新缓存
echo "💾 重新缓存..."
php artisan config:cache

# 4. 验证配置
echo "✅ 验证配置..."
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo '管理员路径: ' . \App\Utils\AdminPathGenerator::getCurrentPath() . PHP_EOL;
echo '前端主题: ' . config('v2board.frontend_theme') . PHP_EOL;
"

echo "🎉 部署配置完成！"
```

使用方法：
```bash
chmod +x deploy.sh
./deploy.sh yourdomain.com
```

## 监控脚本

```bash
#!/bin/bash
# health_check.sh - 健康检查脚本

echo "🔍 V2Board 健康检查..."

# 检查管理员页面
ADMIN_PATH=$(php -r "require_once 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); echo \App\Utils\AdminPathGenerator::getCurrentPath();")

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/$ADMIN_PATH")
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ 管理员页面正常 ($ADMIN_PATH)"
else
    echo "❌ 管理员页面异常 ($HTTP_CODE)"
fi

# 检查普通用户页面
ACCESS_KEY=$(php -r "require_once 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); echo substr(md5(config('app.key')), 0, 8);")

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/?access=$ACCESS_KEY")
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ 用户页面正常 (?access=$ACCESS_KEY)"
else
    echo "❌ 用户页面异常 ($HTTP_CODE)"
fi

# 检查伪装页面
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost/")
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ 伪装页面正常"
else
    echo "❌ 伪装页面异常 ($HTTP_CODE)"
fi
```

## 记住这个教训

**最重要的一点**：每当修改配置文件或路由相关代码后，必须执行：

```bash
php artisan route:clear && php artisan config:clear
```

这是解决大多数 404 和配置问题的关键步骤！
