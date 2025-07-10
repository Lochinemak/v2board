<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stat;
use App\Models\StatServer;
use App\Models\StatUser;
use App\Models\User;
use App\Models\Order;

class StatisticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建一些测试用户
        $this->createTestUsers();
        
        // 创建一些测试订单
        $this->createTestOrders();
        
        // 生成过去30天的统计数据
        $this->generateHistoricalStats();
        
        // 生成服务器统计数据
        $this->generateServerStats();
        
        // 生成用户统计数据
        $this->generateUserStats();
    }
    
    private function createTestUsers()
    {
        $users = [];
        for ($i = 0; $i < 50; $i++) {
            $createdAt = time() - rand(0, 30 * 24 * 3600); // 过去30天内随机时间
            $users[] = [
                'email' => 'test' . $i . '@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'uuid' => \Illuminate\Support\Str::uuid(),
                'token' => \Illuminate\Support\Str::random(32),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }
        
        User::insert($users);
        echo "Created 50 test users\n";
    }
    
    private function createTestOrders()
    {
        $users = User::all();
        $orders = [];
        
        foreach ($users as $user) {
            // 每个用户随机创建0-3个订单
            $orderCount = rand(0, 3);
            for ($i = 0; $i < $orderCount; $i++) {
                $createdAt = time() - rand(0, 30 * 24 * 3600);
                $paidAt = rand(0, 1) ? $createdAt + rand(0, 3600) : null; // 50%概率已支付
                $totalAmount = rand(10, 100) * 100; // 10-100元，以分为单位
                
                $orders[] = [
                    'user_id' => $user->id,
                    'plan_id' => 1,
                    'type' => 1,
                    'period' => 'month',
                    'trade_no' => 'T' . time() . rand(1000, 9999),
                    'total_amount' => $totalAmount,
                    'status' => $paidAt ? 1 : 0, // 1=已支付, 0=未支付
                    'paid_at' => $paidAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }
        
        if (!empty($orders)) {
            Order::insert($orders);
            echo "Created " . count($orders) . " test orders\n";
        }
    }
    
    private function generateHistoricalStats()
    {
        $stats = [];
        
        // 生成过去30天的统计数据
        for ($i = 30; $i >= 1; $i--) {
            $recordAt = strtotime("-{$i} days", strtotime(date('Y-m-d')));
            $endAt = $recordAt + 24 * 3600;
            
            // 计算当天的实际数据
            $registerCount = User::where('created_at', '>=', $recordAt)
                ->where('created_at', '<', $endAt)
                ->count();
                
            $paidCount = Order::where('paid_at', '>=', $recordAt)
                ->where('paid_at', '<', $endAt)
                ->where('status', 1)
                ->count();
                
            $paidTotal = Order::where('paid_at', '>=', $recordAt)
                ->where('paid_at', '<', $endAt)
                ->where('status', 1)
                ->sum('total_amount');
                
            $orderCount = Order::where('created_at', '>=', $recordAt)
                ->where('created_at', '<', $endAt)
                ->count();
                
            $orderTotal = Order::where('created_at', '>=', $recordAt)
                ->where('created_at', '<', $endAt)
                ->sum('total_amount');
            
            $stats[] = [
                'record_at' => $recordAt,
                'record_type' => 'd',
                'order_count' => $orderCount,
                'order_total' => $orderTotal,
                'commission_count' => rand(0, 5),
                'commission_total' => rand(0, 1000),
                'paid_count' => $paidCount,
                'paid_total' => $paidTotal,
                'register_count' => $registerCount,
                'invite_count' => rand(0, $registerCount),
                'transfer_used_total' => rand(1000000000, 10000000000), // 1-10GB
                'created_at' => time(),
                'updated_at' => time(),
            ];
        }
        
        Stat::insert($stats);
        echo "Generated " . count($stats) . " historical stat records\n";
    }
    
    private function generateServerStats()
    {
        $serverStats = [];
        
        // 假设有3个服务器
        for ($serverId = 1; $serverId <= 3; $serverId++) {
            for ($i = 7; $i >= 1; $i--) {
                $recordAt = strtotime("-{$i} days", strtotime(date('Y-m-d')));
                
                $serverStats[] = [
                    'server_id' => $serverId,
                    'server_type' => 'shadowsocks',
                    'u' => rand(1000000000, 5000000000), // 1-5GB上传
                    'd' => rand(5000000000, 20000000000), // 5-20GB下载
                    'record_type' => 'd',
                    'record_at' => $recordAt,
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
            }
        }
        
        StatServer::insert($serverStats);
        echo "Generated " . count($serverStats) . " server stat records\n";
    }
    
    private function generateUserStats()
    {
        $users = User::limit(10)->get(); // 只为前10个用户生成统计
        $userStats = [];
        
        foreach ($users as $user) {
            for ($i = 7; $i >= 1; $i--) {
                $recordAt = strtotime("-{$i} days", strtotime(date('Y-m-d')));
                
                $userStats[] = [
                    'user_id' => $user->id,
                    'server_rate' => 1.0,
                    'u' => rand(100000000, 1000000000), // 100MB-1GB上传
                    'd' => rand(500000000, 5000000000), // 500MB-5GB下载
                    'record_type' => 'd',
                    'record_at' => $recordAt,
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
            }
        }
        
        if (!empty($userStats)) {
            StatUser::insert($userStats);
            echo "Generated " . count($userStats) . " user stat records\n";
        }
    }
}
