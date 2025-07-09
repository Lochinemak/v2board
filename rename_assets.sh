#!/bin/bash

# V2Board 去特征化 - 重命名资源文件夹脚本
# 将明显的特征路径重命名为不易识别的路径

echo "开始重命名资源文件夹..."

# 备份原始结构
if [ ! -d "public_backup" ]; then
    cp -r public public_backup
    echo "已备份原始public目录到public_backup"
fi

# 重命名 assets 为 static
if [ -d "public/assets" ] && [ ! -d "public/static" ]; then
    mv public/assets public/static
    echo "已重命名 assets -> static"
fi

# 重命名 admin 为 panel
if [ -d "public/static/admin" ] && [ ! -d "public/static/panel" ]; then
    mv public/static/admin public/static/panel
    echo "已重命名 admin -> panel"
fi

# 重命名 theme 为 templates
if [ -d "public/theme" ] && [ ! -d "public/templates" ]; then
    mv public/theme public/templates
    echo "已重命名 theme -> templates"
fi

# 重命名 default 为 standard
if [ -d "public/templates/default" ] && [ ! -d "public/templates/standard" ]; then
    mv public/templates/default public/templates/standard
    echo "已重命名 default -> standard"
fi

echo "资源文件夹重命名完成！"
echo "请记得更新相关的配置文件中的路径引用。"
