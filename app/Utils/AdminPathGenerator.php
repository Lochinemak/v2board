<?php

namespace App\Utils;

class AdminPathGenerator
{
    /**
     * 生成更复杂的管理后台路径
     * 使用多重哈希和时间戳，使路径更难被猜测
     */
    public static function generate($appKey = null)
    {
        $appKey = $appKey ?: config('app.key');
        
        // 使用多重哈希算法
        $hash1 = hash('sha256', $appKey);
        $hash2 = hash('md5', $hash1 . 'admin_salt');
        $hash3 = hash('crc32b', $hash2);
        
        // 添加一些随机字符
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $randomStr = '';
        for ($i = 0; $i < 4; $i++) {
            $randomStr .= $chars[hexdec(substr($hash3, $i, 1)) % strlen($chars)];
        }
        
        // 组合生成最终路径
        $finalPath = 'panel_' . $randomStr . '_' . substr($hash3, 0, 6);
        
        return $finalPath;
    }
    
    /**
     * 验证路径是否有效
     */
    public static function validate($path, $appKey = null)
    {
        return $path === self::generate($appKey);
    }
    
    /**
     * 获取当前配置的管理路径
     */
    public static function getCurrentPath()
    {
        return config('v2board.secure_path', 
               config('v2board.frontend_admin_path', 
                      self::generate()));
    }
}
