<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AntiScan
{
    /**
     * 反扫描中间件
     * 检测可疑的扫描行为并返回伪装页面
     */
    public function handle(Request $request, Closure $next)
    {
        // 检查是否为后端节点API白名单路径
        if ($this->isBackendApiPath($request)) {
            // 对于后端API，直接放行
            return $next($request);
        }

        // 检查是否为静态资源路径
        if ($this->isStaticResourcePath($request)) {
            // 对于静态资源，直接放行
            return $next($request);
        }

        // 检测扫描特征
        if ($this->isScanRequest($request)) {
            return $this->getFakeResponse();
        }

        return $next($request);
    }

    /**
     * 检查是否为后端节点API路径
     */
    private function isBackendApiPath(Request $request)
    {
        $path = $request->path();

        // 后端节点API白名单路径
        $backendApiPaths = [
            'api/v1/server/UniProxy/config',    // 获取节点配置信息
            'api/v1/server/UniProxy/user',      // 获取用户列表
            'api/v1/server/UniProxy/alivelist', // 获取在线用户统计
            'api/v1/server/UniProxy/push',      // 上报流量数据
            'api/v1/server/UniProxy/alive',     // 上报在线用户信息
        ];

        foreach ($backendApiPaths as $apiPath) {
            if ($path === $apiPath) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否为静态资源路径
     */
    private function isStaticResourcePath(Request $request)
    {
        $path = $request->path();

        // 静态资源路径白名单
        $staticPaths = [
            'static/',
            'templates/',
            'assets/',  // 兼容旧路径
            'css/',
            'js/',
            'images/',
            'fonts/'
        ];

        foreach ($staticPaths as $staticPath) {
            if (str_starts_with($path, $staticPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检测是否为扫描请求
     */
    private function isScanRequest(Request $request)
    {
        $userAgent = $request->header('User-Agent', '');
        $path = $request->path();

        // 如果有access参数，说明是正常用户访问，不拦截
        if ($request->has('access')) {
            return false;
        }

        // 检测扫描工具的User-Agent
        $scannerUAs = [
            'fofa',
            'shodan',
            'censys',
            'masscan',
            'nmap',
            'zmap',
            'scanner',
            'python-requests',
            'curl',
            'wget',
            'httpx',
            'nuclei',
            'sqlmap'
        ];

        foreach ($scannerUAs as $scannerUA) {
            if (stripos($userAgent, $scannerUA) !== false) {
                return true;
            }
        }

        // 检测可疑路径（但排除根路径）
        if ($path !== '/') {
            $suspiciousPaths = [
                'admin',
                'login',
                'api',
                'v2board',
                'dashboard',
                'config',
                'install',
                'setup',
                'phpmyadmin',
                'wp-admin',
                'manager'
            ];

            foreach ($suspiciousPaths as $suspiciousPath) {
                if (stripos($path, $suspiciousPath) !== false) {
                    return true;
                }
            }
        }

        // 检测明显的自动化工具特征
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return true;
        }

        return false;
    }
    
    /**
     * 返回伪装的响应
     */
    private function getFakeResponse()
    {
        $fakeHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to nginx!</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #333; }
        p { color: #666; line-height: 1.6; }
    </style>
</head>
<body>
    <h1>Welcome to nginx!</h1>
    <p>If you see this page, the nginx web server is successfully installed and working.</p>
    <p>For online documentation and support please refer to <a href="http://nginx.org/">nginx.org</a>.</p>
    <p>Commercial support is available at <a href="http://nginx.com/">nginx.com</a>.</p>
    <p><em>Thank you for using nginx.</em></p>
</body>
</html>';
        
        return response($fakeHtml, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Server', 'nginx/1.18.0')
            ->header('X-Powered-By', 'nginx');
    }
}
