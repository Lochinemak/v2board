<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Utils\AdminPathGenerator;

class GenerateAccessUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'access:url {--domain=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成访问真实首页和管理后台的URL';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $domain = $this->option('domain') ?: 'yourdomain.com';
        
        // 确保域名格式正确
        if (!str_starts_with($domain, 'http')) {
            $domain = 'https://' . $domain;
        }
        $domain = rtrim($domain, '/');
        
        // 生成访问参数
        $dailyKey = md5(config('app.key') . date('Y-m-d'));
        $simpleKey = substr(md5(config('app.key')), 0, 8);

        // 生成管理后台路径
        $adminPath = AdminPathGenerator::getCurrentPath();

        $this->info('=== V2Board 访问链接生成器 ===');
        $this->line('');

        $this->info('📱 真实首页访问链接:');
        $this->line('方式1 (每日更新): ' . $domain . '/?access=' . $dailyKey);
        $this->line('方式2 (固定密钥): ' . $domain . '/?access=' . $simpleKey);
        $this->line('');

        $this->info('🔧 管理后台访问链接:');
        $this->line($domain . '/' . $adminPath);
        $this->line('');

        $this->info('📅 今日访问参数: ' . $dailyKey . ' (有效期: ' . date('Y-m-d') . ')');
        $this->info('🔑 固定访问参数: ' . $simpleKey . ' (永久有效)');
        $this->line('');

        $this->warn('💡 建议: 日常使用固定密钥，紧急情况使用每日密钥');
        $this->line('');

        $this->info('🔄 快速生成命令:');
        $this->line('php artisan access:url --domain=yourdomain.com');
        
        return 0;
    }
}
