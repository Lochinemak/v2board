<?php

namespace App\Utils;

class AdminPathGenerator
{
    /**
     * 获取管理后台路径
     */
    public static function getCurrentPath()
    {
        return config('v2board.frontend_admin_path', 'admin');
    }

    /**
     * 为了兼容性保留的方法
     */
    public static function generate($appKey = null)
    {
        return self::getCurrentPath();
    }

    /**
     * 为了兼容性保留的方法
     */
    public static function validate($path, $appKey = null)
    {
        return $path === self::getCurrentPath();
    }
}
