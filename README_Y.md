# V2Board 去特征化版本

本版本对原版V2Board进行了全面的去特征化改造，最大限度减少被FOFA等扫描工具识别的风险，同时保持所有原有功能。

## 🛡️ 主要特性

### 1. 伪装机制
- **默认伪装页面**：访问首页显示企业IT服务网站
- **反扫描检测**：自动识别扫描工具并返回伪装内容
- **特征信息隐藏**：移除所有V2Board相关标识

### 2. 访问控制
- **双重访问机制**：固定密钥 + 每日密钥
- **复杂管理路径**：使用多重哈希生成难以猜测的后台路径
- **白名单保护**：后端API不受反扫描影响

### 3. 资源路径混淆
- **静态资源重命名**：`assets` → `static`，`admin` → `panel`
- **主题路径修改**：`theme` → `templates`
- **自动重定向**：兼容旧路径访问

## 🔑 访问路径生成机制

### 真实首页访问
```bash
# 生成访问链接命令
php artisan access:url --domain=yourdomain.com
```

**两种访问方式：**
- **固定密钥**：`https://yourdomain.com/?access=84c4bf5d` (永久有效，日常使用)
- **每日密钥**：`https://yourdomain.com/?access=29fc618bdb7da1945b015e5759a15499` (每天变化)

### 管理后台访问
- **路径格式**：`panel_xxxx_xxxxxx`
- **生成算法**：基于APP_KEY的复杂哈希算法
- **示例路径**：`https://yourdomain.com/panel_makh_c0a7a9`

### 伪装首页
- **默认访问**：`https://yourdomain.com/` 显示企业IT服务页面

## 🚀 部署指南

### 1. 基础环境配置
```bash
# 1. 创建.env文件
cp .env.example .env

# 2. 生成APP_KEY
php artisan key:generate

# 3. 配置数据库连接
# 编辑.env文件中的数据库配置
```

### 2. 数据库初始化
```bash
# 1. 创建数据库
mysql -u root -p -e "CREATE DATABASE v2board CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. 导入初始化脚本
mysql -u username -p database_name < database/install.sql

# 3. 运行安装命令
php artisan v2board:install
```

### 3. 去特征化专用配置
```bash
# 1. 创建env.js文件（管理后台必需）
cp public/static/panel/env.example.js public/static/panel/env.js

# 2. 清除所有缓存
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. 重新生成缓存
php artisan config:cache
php artisan route:cache
```

### 4. 获取访问链接
```bash
# 生成当前域名的访问链接
php artisan access:url --domain=yourdomain.com

# 输出示例：
# 📱 真实首页访问链接:
# 方式1 (每日更新): https://yourdomain.com/?access=29fc618bdb7da1945b015e5759a15499
# 方式2 (固定密钥): https://yourdomain.com/?access=84c4bf5d
# 
# 🔧 管理后台访问链接:
# https://yourdomain.com/panel_makh_c0a7a9
```

## 🛡️ 安全特性

### 1. 反扫描机制
- **扫描工具检测**：自动识别python-requests、curl、wget等扫描工具
- **伪装响应**：返回nginx欢迎页面或企业网站页面
- **白名单保护**：后端API `/api/v1/server/UniProxy/*` 不受影响

### 2. 后端API保护
- **正确token**：正常返回数据
- **错误token**：返回通用错误 `{"error":"Authentication failed","code":401}`
- **无token**：返回通用错误 `{"error":"Authentication required","code":401}`
- **路径隐藏**：不暴露PHP警告和系统路径

### 3. 资源路径混淆
- **旧路径自动重定向**：`/assets/admin/*` → `/static/panel/*`
- **主题路径重定向**：`/theme/*` → `/templates/*`

## 📝 日常维护

### 获取访问链接
```bash
# 每次需要访问时运行
php artisan access:url --domain=yourdomain.com
```

### 更换访问密钥
```bash
# 重新生成APP_KEY会更换所有密钥
php artisan key:generate
php artisan config:cache
```

### 检查系统状态
```bash
# 测试后端API是否正常
curl "https://yourdomain.com/api/v1/server/UniProxy/alive?token=your_server_token"
```

## ⚠️ 重要提醒

1. **备份重要**：部署前务必备份原始代码和数据库
2. **域名配置**：确保在生产环境中使用正确的域名
3. **HTTPS配置**：生产环境建议使用HTTPS
4. **定期更新**：定期运行 `php artisan access:url` 获取最新访问链接
5. **服务器配置**：确保web服务器正确配置PHP和Laravel

## 🎯 访问测试清单

部署完成后，请测试以下访问：

- [ ] `https://yourdomain.com/` - 显示伪装页面
- [ ] `https://yourdomain.com/?access=固定密钥` - 显示真实首页
- [ ] `https://yourdomain.com/panel_xxxx_xxxxxx` - 管理后台正常
- [ ] 后端API正常上报数据
- [ ] 扫描工具访问返回伪装页面

## 🔧 技术实现

### 主要修改内容
1. **默认配置伪装** - 将V2Board改为CloudPanel，描述改为企业IT服务
2. **静态资源路径混淆** - assets→static, admin→panel, theme→templates
3. **HTTP响应头伪装** - 模拟nginx/Apache服务器
4. **错误信息通用化** - 所有认证错误统一为"Access denied"
5. **管理后台路径加强** - 使用复杂算法生成难以猜测的路径
6. **反扫描机制** - 检测扫描工具并返回伪装页面
7. **伪装首页** - 默认显示企业网站，需特殊参数访问真实页面

### 兼容性说明
- **API路径保持不变**：`/api/v1` 和 `/api/v2` 路径未修改，便于后端对接
- **数据库表前缀保持不变**：继续使用 `v2_` 前缀，避免影响功能
- **向后兼容**：旧的资源路径会自动重定向到新路径

## 📞 支持

如有问题，请检查：
1. 是否正确执行了所有部署步骤
2. 是否创建了必要的配置文件
3. 是否清除了缓存并重新生成
4. 网络请求是否被防火墙拦截

---

**注意**：本版本主要用于减少被自动化扫描工具识别的风险，但不能完全保证安全。请结合其他安全措施使用。
