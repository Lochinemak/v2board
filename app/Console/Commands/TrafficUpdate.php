<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Services\StatisticalService;

class TrafficUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'traffic:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '流量更新任务';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', -1);

        // 支持多种Redis键名格式
        $uploadKeys = [
            'v2board_upload_traffic',
            'privatetracker_database_v2board_upload_traffic'
        ];

        $downloadKeys = [
            'v2board_download_traffic',
            'privatetracker_database_v2board_download_traffic'
        ];

        $uploads = [];
        $downloads = [];

        // 尝试从不同的键名获取数据
        foreach ($uploadKeys as $key) {
            // 尝试使用Laravel Redis（带前缀）
            $data = Redis::hgetall($key);
            if (!empty($data)) {
                $uploads = $uploads + $data; // 使用+操作符保持键名
                Redis::del($key);
                $this->info("从 {$key} (Laravel Redis) 获取到上传流量数据: " . count($data) . " 个用户");
            } else {
                // 尝试使用原生Redis连接（无前缀）
                try {
                    $redis = new \Redis();
                    $redis->connect('127.0.0.1', 6379);
                    $data = $redis->hGetAll($key);
                    if (!empty($data)) {
                        $this->info("原始上传数据: " . json_encode($data));
                        $uploads = $uploads + $data; // 使用+操作符保持键名
                        $redis->del($key);
                        $this->info("从 {$key} (原生 Redis) 获取到上传流量数据: " . count($data) . " 个用户");
                    }
                    $redis->close();
                } catch (\Exception $e) {
                    // 忽略连接错误
                }
            }
        }

        foreach ($downloadKeys as $key) {
            // 尝试使用Laravel Redis（带前缀）
            $data = Redis::hgetall($key);
            if (!empty($data)) {
                $downloads = $downloads + $data; // 使用+操作符保持键名
                Redis::del($key);
                $this->info("从 {$key} (Laravel Redis) 获取到下载流量数据: " . count($data) . " 个用户");
            } else {
                // 尝试使用原生Redis连接（无前缀）
                try {
                    $redis = new \Redis();
                    $redis->connect('127.0.0.1', 6379);
                    $data = $redis->hGetAll($key);
                    if (!empty($data)) {
                        $this->info("原始下载数据: " . json_encode($data));
                        $downloads = $downloads + $data; // 使用+操作符保持键名
                        $redis->del($key);
                        $this->info("从 {$key} (原生 Redis) 获取到下载流量数据: " . count($data) . " 个用户");
                    }
                    $redis->close();
                } catch (\Exception $e) {
                    // 忽略连接错误
                }
            }
        }

        if (empty($uploads) && empty($downloads)) {
            $this->info('没有流量数据需要更新');
            return;
        }

        // 合并用户ID列表
        $this->info('上传数据键: ' . json_encode(array_keys($uploads)));
        $this->info('下载数据键: ' . json_encode(array_keys($downloads)));
        $userIds = array_unique(array_merge(array_keys($uploads), array_keys($downloads)));
        $this->info('需要更新的用户ID: ' . implode(', ', $userIds));

        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $this->info('数据库中找到的用户数量: ' . count($users));

        $time = time();
        $updatedCount = 0;

        // 初始化统计服务
        $statService = new StatisticalService();
        $statService->setStartAt(strtotime(date('Y-m-d')));

        try {
            DB::beginTransaction();
            foreach ($userIds as $userId) {
                if (!isset($users[$userId])) {
                    continue;
                }

                $user = $users[$userId];
                $uploadTraffic = isset($uploads[$userId]) ? (int)$uploads[$userId] : 0;
                $downloadTraffic = isset($downloads[$userId]) ? (int)$downloads[$userId] : 0;

                if ($uploadTraffic > 0 || $downloadTraffic > 0) {
                    $user->update([
                        't' => $time,
                        'u' => $user->u + $uploadTraffic,
                        'd' => $user->d + $downloadTraffic,
                    ]);
                    $updatedCount++;

                    // 记录到统计服务（使用默认倍率1.0）
                    $statService->statUser(1.0, $userId, $uploadTraffic, $downloadTraffic);

                    $this->info("用户 {$userId}: 上传 +" . $this->formatBytes($uploadTraffic) . ", 下载 +" . $this->formatBytes($downloadTraffic));
                }
            }
            DB::commit();
            $this->info("成功更新 {$updatedCount} 个用户的流量数据");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('流量更新失败: ' . $e->getMessage());
            return;
        }
    }

    /**
     * 格式化字节数
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
