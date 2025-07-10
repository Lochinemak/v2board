<?php

use Illuminate\Database\Seeder;
use App\Models\Stat;
use App\Models\StatServer;
use App\Models\StatUser;

class StatisticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "开始生成测试统计数据...\n";
        
        // 清空现有统计数据
        Stat::truncate();
        StatServer::truncate();
        StatUser::truncate();
        
        // 生成过去30天的统计数据
        for ($i = 30; $i >= 1; $i--) {
            $recordAt = strtotime("-{$i} days", strtotime(date('Y-m-d')));
            
            Stat::create([
                'record_at' => $recordAt,
                'record_type' => 'd',
                'order_count' => rand(5, 20),
                'order_total' => rand(1000, 5000) * 100, // 10-50元，以分为单位
                'commission_count' => rand(0, 5),
                'commission_total' => rand(0, 1000),
                'paid_count' => rand(3, 15),
                'paid_total' => rand(800, 4000) * 100,
                'register_count' => rand(2, 10),
                'invite_count' => rand(0, 5),
                'transfer_used_total' => rand(1000000000, 10000000000), // 1-10GB
            ]);
        }
        
        // 生成服务器统计数据
        for ($serverId = 1; $serverId <= 3; $serverId++) {
            for ($i = 7; $i >= 1; $i--) {
                $recordAt = strtotime("-{$i} days", strtotime(date('Y-m-d')));
                
                StatServer::create([
                    'server_id' => $serverId,
                    'server_type' => 'shadowsocks',
                    'u' => rand(1000000000, 5000000000), // 1-5GB上传
                    'd' => rand(5000000000, 20000000000), // 5-20GB下载
                    'record_type' => 'd',
                    'record_at' => $recordAt,
                ]);
            }
        }
        
        // 生成用户统计数据
        for ($userId = 1; $userId <= 10; $userId++) {
            for ($i = 7; $i >= 1; $i--) {
                $recordAt = strtotime("-{$i} days", strtotime(date('Y-m-d')));
                
                StatUser::create([
                    'user_id' => $userId,
                    'server_rate' => 1.0,
                    'u' => rand(100000000, 1000000000), // 100MB-1GB上传
                    'd' => rand(500000000, 5000000000), // 500MB-5GB下载
                    'record_type' => 'd',
                    'record_at' => $recordAt,
                ]);
            }
        }
        
        echo "测试数据生成完成！\n";
        echo "统计记录数: " . Stat::count() . "\n";
        echo "服务器统计记录数: " . StatServer::count() . "\n";
        echo "用户统计记录数: " . StatUser::count() . "\n";
    }
}
