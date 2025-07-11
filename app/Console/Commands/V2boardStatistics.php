<?php

namespace App\Console\Commands;

use App\Models\StatServer;
use App\Models\StatUser;
use App\Services\StatisticalService;
use Illuminate\Console\Command;
use App\Models\Stat;
use Illuminate\Support\Facades\DB;

class V2boardStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2board:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计任务';

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
        $startAt = microtime(true);
        ini_set('memory_limit', -1);

        $this->info('开始执行统计任务...');

        // 执行用户统计
        $this->info('执行用户流量统计...');
        $this->statUser();

        // 执行服务器统计
        $this->info('执行服务器流量统计...');
        $this->statServer();

        // 执行总体统计
        $this->info('执行总体统计...');
        $this->stat();

        $executionTime = (microtime(true) - $startAt);
        $this->info('统计任务执行完毕。耗时: ' . round($executionTime, 2) . ' 秒');
        info('统计任务执行完毕。耗时:' . $executionTime);
    }

    private function statServer()
    {
        try {
            DB::beginTransaction();
            $createdAt = time();
            $recordAt = strtotime('-1 day', strtotime(date('Y-m-d')));
            $statService = new StatisticalService();
            $statService->setStartAt($recordAt);
            $statService->setServerStats();
            $stats = $statService->getStatServer();
            foreach ($stats as $stat) {
                if (!StatServer::insert([
                    'server_id' => $stat['server_id'],
                    'server_type' => $stat['server_type'],
                    'u' => $stat['u'],
                    'd' => $stat['d'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'record_type' => 'd',
                    'record_at' => $recordAt
                ])) {
                    throw new \Exception('stat server fail');
                }
            }
            DB::commit();
            $statService->clearStatServer();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error($e->getMessage(), ['exception' => $e]);
        }
    }

    private function statUser()
    {
        try {
            DB::beginTransaction();
            $createdAt = time();
            $recordAt = strtotime('-1 day', strtotime(date('Y-m-d')));

            $this->info('统计时间范围: ' . date('Y-m-d H:i:s', $recordAt) . ' 到 ' . date('Y-m-d H:i:s', strtotime(date('Y-m-d'))));

            $statService = new StatisticalService();
            $statService->setStartAt($recordAt);
            $statService->setUserStats();
            $stats = $statService->getStatUser();

            $this->info('获取到 ' . count($stats) . ' 条用户统计数据');

            $insertedCount = 0;
            foreach ($stats as $stat) {
                if (StatUser::insert([
                    'user_id' => $stat['user_id'],
                    'u' => $stat['u'],
                    'd' => $stat['d'],
                    'server_rate' => $stat['server_rate'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'record_type' => 'd',
                    'record_at' => $recordAt
                ])) {
                    $insertedCount++;
                    $this->info('用户 ' . $stat['user_id'] . ': 上传 ' . $this->formatBytes($stat['u']) . ', 下载 ' . $this->formatBytes($stat['d']) . ', 倍率 ' . $stat['server_rate']);
                } else {
                    throw new \Exception('stat user fail for user ' . $stat['user_id']);
                }
            }

            DB::commit();
            $this->info('成功插入 ' . $insertedCount . ' 条用户统计记录');
            $statService->clearStatUser();
        } catch (\Exception $e) {
            DB::rollback();
            $this->error('用户统计失败: ' . $e->getMessage());
            \Log::error($e->getMessage(), ['exception' => $e]);
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

    private function stat()
    {
        try {
            $endAt = strtotime(date('Y-m-d'));
            $startAt = strtotime('-1 day', $endAt);
            $statisticalService = new StatisticalService();
            $statisticalService->setStartAt($startAt);
            $statisticalService->setEndAt($endAt);
            $data = $statisticalService->generateStatData();
            $data['record_at'] = $startAt;
            $data['record_type'] = 'd';
            $statistic = Stat::where('record_at', $startAt)
                ->where('record_type', 'd')
                ->first();
            if ($statistic) {
                $statistic->update($data);
                return;
            }
            Stat::create($data);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
