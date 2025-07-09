<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HideServerInfo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // 移除可能暴露框架信息的响应头
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        
        // 添加伪装的响应头
        $response->headers->set('Server', 'Apache/2.4.41');
        $response->headers->set('X-Powered-By', 'Express');
        
        // 添加安全相关的响应头
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // 添加一些常见的缓存控制头
        if (!$response->headers->has('Cache-Control')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        }
        
        return $response;
    }
}
