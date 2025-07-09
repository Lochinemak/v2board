<?php

namespace App\Http\Middleware;

use Closure;

class CORS
{
    public function handle($request, Closure $next)
    {
        $origin = $request->header('origin');
        if (empty($origin)) {
            $referer = $request->header('referer');
            if (!empty($referer) && preg_match("/^((https|http):\/\/)?([^\/]+)/i", $referer, $matches)) {
                $origin = $matches[0];
            }
        }
        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', trim($origin, '/'));
        $response->header('Access-Control-Allow-Methods', 'GET,POST,OPTIONS,HEAD');
        $response->header('Access-Control-Allow-Headers', 'Origin,Content-Type,Accept,Authorization,X-Request-With');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', 10080);

        // 添加伪装响应头，隐藏真实服务器特征
        $response->header('Server', 'nginx/1.18.0');
        $response->header('X-Powered-By', 'PHP/8.1.0');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-XSS-Protection', '1; mode=block');

        return $response;
    }
}
