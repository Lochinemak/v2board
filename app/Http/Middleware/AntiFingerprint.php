<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AntiFingerprint
{
    /**
     * Handle an incoming request.
     * 对于未授权的请求，返回通用错误信息，避免暴露系统特征
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 检查是否为后端节点API白名单路径
        if ($this->isBackendApiPath($request)) {
            // 对于后端API，直接放行，不进行特征过滤
            return $next($request);
        }

        try {
            $response = $next($request);

            // 如果是错误响应，检查是否需要隐藏特征信息
            if ($response->getStatusCode() >= 400) {
                $content = $response->getContent();

                // 检查是否包含V2Board特征信息
                if ($this->containsFingerprint($content)) {
                    return $this->createGenericErrorResponse($response->getStatusCode());
                }
            }

            return $response;

        } catch (\Exception $e) {
            // 对于异常情况，返回通用错误
            return $this->createGenericErrorResponse(500);
        }
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
     * 检查内容是否包含特征信息
     */
    private function containsFingerprint($content)
    {
        $fingerprints = [
            'V2Board',
            'v2board',
            'V2Ray',
            'v2ray',
            'Shadowsocks',
            'shadowsocks',
            'Trojan',
            'trojan',
            'subscription',
            'subscribe',
            'proxy',
            'vpn',
            'gfw',
            'clash',
            'surge',
            'quantumult'
        ];
        
        foreach ($fingerprints as $fingerprint) {
            if (stripos($content, $fingerprint) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 创建通用错误响应
     */
    private function createGenericErrorResponse($statusCode)
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable'
        ];
        
        $message = $messages[$statusCode] ?? 'Error';
        
        return response()->json([
            'error' => $message,
            'code' => $statusCode,
            'timestamp' => time()
        ], $statusCode)->header('Content-Type', 'application/json');
    }
}
