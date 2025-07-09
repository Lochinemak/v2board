<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ServerAuth
{
    /**
     * Handle an incoming request.
     * 专门处理后端节点服务器的认证，确保错误响应不暴露系统信息
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 抑制PHP Deprecated警告，避免暴露系统路径
        $originalErrorReporting = error_reporting();
        error_reporting($originalErrorReporting & ~E_DEPRECATED);

        try {
            // 检查token
            $token = $request->input('token');
            if (empty($token)) {
                return $this->createCleanErrorResponse('Authentication required');
            }

            if ($token !== config('v2board.server_token')) {
                return $this->createCleanErrorResponse('Authentication failed');
            }

            // 验证节点信息
            $nodeType = $request->input('node_type');
            $nodeId = $request->input('node_id');

            if (empty($nodeType) || empty($nodeId)) {
                return $this->createCleanErrorResponse('Invalid node parameters');
            }

            // 将认证信息添加到请求中，供控制器使用
            $request->merge([
                'authenticated' => true,
                'node_type' => $nodeType === 'v2ray' ? 'vmess' : ($nodeType === 'hysteria2' ? 'hysteria' : $nodeType),
                'node_id' => $nodeId
            ]);

            $response = $next($request);

            // 恢复错误报告级别
            error_reporting($originalErrorReporting);

            return $response;

        } catch (\Exception $e) {
            // 恢复错误报告级别
            error_reporting($originalErrorReporting);

            // 返回干净的错误响应
            return $this->createCleanErrorResponse('Server error');
        }
    }
    
    /**
     * 创建干净的错误响应，不暴露系统信息
     */
    private function createCleanErrorResponse($message)
    {
        return response()->json([
            'error' => $message,
            'code' => 401
        ], 401)->header('Content-Type', 'application/json');
    }
}
