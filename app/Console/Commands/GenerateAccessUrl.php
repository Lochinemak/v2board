<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Utils\AdminPathGenerator;
use Illuminate\Support\Facades\File;

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
        // 首先清除缓存，确保配置和路由是最新的
        $this->call('route:clear');
        $this->call('config:clear');
        $this->info('🧹 已清除路由和配置缓存');

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
        $adminPath = AdminPathGenerator::generate();

        // 更新配置文件中的 secure_path
        $this->updateSecurePath($adminPath);

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

    /**
     * 更新配置文件中的 secure_path
     */
    private function updateSecurePath($adminPath)
    {
        $configPath = base_path('config/v2board.php');

        if (!file_exists($configPath)) {
            $this->warn('⚠️  配置文件不存在，跳过更新');
            return;
        }

        try {
            // 读取当前配置
            $config = include $configPath;

            // 更新 secure_path
            $config['secure_path'] = $adminPath;

            // 写回配置文件
            $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
            file_put_contents($configPath, $configContent);

            // 清除配置缓存
            $this->call('config:cache');

            $this->info('✅ 已更新配置文件中的管理后台路径');

        } catch (\Exception $e) {
            $this->error('❌ 更新配置文件失败: ' . $e->getMessage());
        }
    }
}
