# Linux.do OAuth Integration Database Scripts

这些脚本用于为V2Board添加Linux.do OAuth登录支持所需的数据库字段。

## 文件说明

### 1. `linux_do_oauth.sql` (推荐)
- **功能**: 智能检测并添加OAuth字段
- **特点**: 
  - 自动检测字段是否已存在，避免重复执行错误
  - 包含详细的注释和验证查询
  - 添加性能优化索引
  - 显示执行结果和表结构

### 2. `linux_do_oauth_simple.sql` (简单版本)
- **功能**: 直接添加OAuth字段
- **特点**: 
  - 简单直接的SQL语句
  - 适合一次性执行
  - 包含基本的验证查询

## 使用方法

### 方法一：使用智能脚本 (推荐)
```bash
mysql -u root -p your_database_name < database/linux_do_oauth.sql
```

### 方法二：使用简单脚本
```bash
mysql -u root -p your_database_name < database/linux_do_oauth_simple.sql
```

### 方法三：手动执行
```sql
-- 添加OAuth相关字段
ALTER TABLE `v2_user` 
ADD COLUMN `oauth_provider` varchar(50) NULL COMMENT 'OAuth提供商' AFTER `email`,
ADD COLUMN `oauth_provider_id` varchar(100) NULL COMMENT 'OAuth提供商用户ID' AFTER `oauth_provider`,
ADD COLUMN `oauth_avatar` varchar(500) NULL COMMENT 'OAuth用户头像URL' AFTER `oauth_provider_id`,
ADD COLUMN `oauth_name` varchar(100) NULL COMMENT 'OAuth用户显示名称' AFTER `oauth_avatar`,
ADD COLUMN `is_oauth_user` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为OAuth用户' AFTER `oauth_name`;

-- 添加索引优化查询性能
ALTER TABLE `v2_user` 
ADD INDEX `idx_oauth_provider` (`oauth_provider`, `oauth_provider_id`),
ADD INDEX `idx_is_oauth_user` (`is_oauth_user`);
```

## 添加的字段说明

| 字段名 | 类型 | 说明 | 默认值 |
|--------|------|------|--------|
| `oauth_provider` | varchar(50) | OAuth提供商标识 (如: linuxdo) | NULL |
| `oauth_provider_id` | varchar(100) | OAuth提供商中的用户ID | NULL |
| `oauth_avatar` | varchar(500) | OAuth用户头像URL | NULL |
| `oauth_name` | varchar(100) | OAuth用户显示名称 | NULL |
| `is_oauth_user` | tinyint(1) | 是否为OAuth用户标识 | 0 |

## 添加的索引

- `idx_oauth_provider`: 复合索引 (`oauth_provider`, `oauth_provider_id`) - 用于快速查找OAuth用户
- `idx_is_oauth_user`: 单列索引 (`is_oauth_user`) - 用于快速筛选OAuth用户

## 验证安装

执行以下SQL查询验证字段是否正确添加：

```sql
-- 查看OAuth相关字段
SELECT 
    COLUMN_NAME as 'Field Name',
    COLUMN_TYPE as 'Type',
    IS_NULLABLE as 'Nullable',
    COLUMN_DEFAULT as 'Default',
    COLUMN_COMMENT as 'Comment'
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'v2_user' 
  AND (COLUMN_NAME LIKE '%oauth%' OR COLUMN_NAME = 'is_oauth_user')
ORDER BY ORDINAL_POSITION;
```

## 注意事项

1. **备份数据库**: 执行脚本前请备份数据库
2. **权限要求**: 需要ALTER TABLE权限
3. **重复执行**: `linux_do_oauth.sql` 可以安全地重复执行
4. **版本兼容**: 适用于V2Board所有版本

## 故障排除

### 常见错误

1. **字段已存在错误**
   - 使用 `linux_do_oauth.sql` 脚本可以避免此问题
   - 或者先删除已存在的字段再重新添加

2. **权限不足**
   - 确保数据库用户有ALTER TABLE权限
   - 使用管理员账户执行脚本

3. **表不存在**
   - 确保已经运行了V2Board的初始化脚本 `install.sql`
   - 检查数据库名称是否正确

## 相关配置

执行脚本后，还需要配置以下内容以启用Linux.do OAuth：

1. 在 `.env` 文件中添加：
```env
LINUXDO_CLIENT_ID=your_client_id
LINUXDO_CLIENT_SECRET=your_client_secret
```

2. 确保相关的OAuth服务类和控制器已正确配置

## 支持

如有问题，请检查：
- V2Board版本兼容性
- 数据库连接配置
- OAuth相关代码是否已正确部署
