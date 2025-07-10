#!/bin/bash

# V2Board 服务器更新脚本
# 用途：安全地将服务器从去特征化版本更新到标准版本

set -e  # 遇到错误立即退出

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日志函数
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检查是否为root用户
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_warning "检测到root用户，请确保这是预期的"
        read -p "继续? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# 检查必要的命令
check_dependencies() {
    log_info "检查依赖..."
    
    local deps=("php" "composer" "mysql" "git")
    for dep in "${deps[@]}"; do
        if ! command -v $dep &> /dev/null; then
            log_error "$dep 未安装"
            exit 1
        fi
    done
    
    log_success "依赖检查通过"
}

# 创建备份
create_backup() {
    local backup_dir="v2board_backup_$(date +%Y%m%d_%H%M%S)"
    
    log_info "创建备份到 $backup_dir..."
    
    # 备份代码
    cp -r . "../$backup_dir"
    
    # 备份数据库
    if [ -f ".env" ]; then
        local db_name=$(grep DB_DATABASE .env | cut -d '=' -f2)
        local db_user=$(grep DB_USERNAME .env | cut -d '=' -f2)
        local db_pass=$(grep DB_PASSWORD .env | cut -d '=' -f2)
        
        if [ ! -z "$db_name" ]; then
            log_info "备份数据库 $db_name..."
            mysqldump -u "$db_user" -p"$db_pass" "$db_name" > "../${backup_dir}_database.sql"
        fi
    fi
    
    echo "$backup_dir" > .backup_info
    log_success "备份完成: $backup_dir"
}

# 停止服务
stop_services() {
    log_info "停止服务..."
    
    # 检测并停止Web服务器
    if systemctl is-active --quiet nginx; then
        sudo systemctl stop nginx
        echo "nginx" > .stopped_services
        log_info "已停止 Nginx"
    elif systemctl is-active --quiet apache2; then
        sudo systemctl stop apache2
        echo "apache2" > .stopped_services
        log_info "已停止 Apache2"
    fi
    
    # 停止队列进程
    if command -v supervisorctl &> /dev/null; then
        sudo supervisorctl stop v2board:* 2>/dev/null || true
        log_info "已停止队列进程"
    fi
}

# 更新代码
update_code() {
    log_info "更新代码..."
    
    # 暂存本地修改
    git stash push -m "Auto stash before update $(date)"
    
    # 拉取最新代码
    git pull origin main || git pull origin master
    
    # 更新依赖
    composer install --no-dev --optimize-autoloader
    
    log_success "代码更新完成"
}

# 更新配置
update_config() {
    log_info "更新配置文件..."
    
    # 备份当前配置
    cp config/v2board.php config/v2board.php.pre_update
    
    # 使用PHP脚本更新配置
    php -r "
    \$config = include 'config/v2board.php';
    
    // 恢复标准配置
    \$config['app_name'] = 'V2Board';
    \$config['app_description'] = 'V2Board is best';
    
    // 转换路径配置
    if (isset(\$config['secure_path'])) {
        \$config['frontend_admin_path'] = \$config['secure_path'];
        unset(\$config['secure_path']);
    }
    
    // 写回配置文件
    file_put_contents('config/v2board.php', '<?php\nreturn ' . var_export(\$config, true) . ';\n');
    echo '配置文件已更新\n';
    "
    
    log_success "配置更新完成"
}

# 清除缓存
clear_cache() {
    log_info "清除缓存..."
    
    php artisan route:clear
    php artisan config:clear
    php artisan view:clear
    php artisan cache:clear
    
    log_info "重新生成缓存..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    log_success "缓存更新完成"
}

# 设置权限
set_permissions() {
    log_info "设置文件权限..."
    
    chmod -R 755 storage bootstrap/cache
    
    # 检测Web服务器用户
    if id "www-data" &>/dev/null; then
        chown -R www-data:www-data storage bootstrap/cache
    elif id "nginx" &>/dev/null; then
        chown -R nginx:nginx storage bootstrap/cache
    elif id "apache" &>/dev/null; then
        chown -R apache:apache storage bootstrap/cache
    fi
    
    log_success "权限设置完成"
}

# 启动服务
start_services() {
    log_info "启动服务..."
    
    if [ -f ".stopped_services" ]; then
        local service=$(cat .stopped_services)
        sudo systemctl start $service
        rm .stopped_services
        log_info "已启动 $service"
    fi
    
    # 启动队列进程
    if command -v supervisorctl &> /dev/null; then
        sudo supervisorctl start v2board:* 2>/dev/null || true
        log_info "已启动队列进程"
    fi
    
    log_success "服务启动完成"
}

# 测试访问
test_access() {
    log_info "测试访问..."
    
    local domain="localhost"
    if [ -f ".env" ]; then
        domain=$(grep APP_URL .env | cut -d '=' -f2 | sed 's|https\?://||' | sed 's|/.*||') || domain="localhost"
    fi
    
    # 等待服务启动
    sleep 3
    
    # 测试首页
    if curl -s -f "http://$domain/" > /dev/null; then
        log_success "首页访问正常"
    else
        log_warning "首页访问可能有问题"
    fi
    
    # 测试管理后台
    if curl -s -f "http://$domain/admin" > /dev/null; then
        log_success "管理后台访问正常"
    else
        log_warning "管理后台访问可能有问题"
    fi
}

# 回滚函数
rollback() {
    log_error "更新失败，开始回滚..."
    
    if [ -f ".backup_info" ]; then
        local backup_dir=$(cat .backup_info)
        
        # 停止服务
        stop_services
        
        # 恢复代码
        rm -rf ./*
        cp -r "../$backup_dir/"* .
        
        # 恢复数据库
        if [ -f "../${backup_dir}_database.sql" ]; then
            local db_name=$(grep DB_DATABASE .env | cut -d '=' -f2)
            local db_user=$(grep DB_USERNAME .env | cut -d '=' -f2)
            local db_pass=$(grep DB_PASSWORD .env | cut -d '=' -f2)
            
            mysql -u "$db_user" -p"$db_pass" "$db_name" < "../${backup_dir}_database.sql"
        fi
        
        # 清除缓存
        php artisan route:clear
        php artisan config:clear
        php artisan cache:clear
        
        # 启动服务
        start_services
        
        log_success "回滚完成"
    else
        log_error "找不到备份信息，请手动回滚"
    fi
}

# 主函数
main() {
    echo "=================================="
    echo "V2Board 服务器更新脚本"
    echo "从去特征化版本更新到标准版本"
    echo "=================================="
    
    # 确认更新
    log_warning "本次更新将移除所有访问控制机制"
    log_warning "更新后访问方式："
    log_warning "- 首页: https://yourdomain.com/"
    log_warning "- 管理后台: https://yourdomain.com/admin"
    echo
    read -p "确认继续更新? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "更新已取消"
        exit 0
    fi
    
    # 设置错误处理
    trap rollback ERR
    
    # 执行更新步骤
    check_root
    check_dependencies
    create_backup
    stop_services
    update_code
    update_config
    clear_cache
    set_permissions
    start_services
    test_access
    
    # 清理临时文件
    rm -f .backup_info .stopped_services
    
    log_success "更新完成！"
    echo
    log_info "新的访问方式："
    log_info "- 首页: https://yourdomain.com/"
    log_info "- 管理后台: https://yourdomain.com/admin"
    echo
    log_info "请测试所有功能是否正常"
}

# 运行主函数
main "$@"
