<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuppressDeprecationWarnings
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
        // 设置错误报告级别，排除弃用警告
        $originalErrorReporting = error_reporting();
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            $response = $next($request);

            // 只处理JSON响应的内容清理
            if ($response->headers->get('Content-Type') === 'application/json') {
                $content = $response->getContent();

                // 清理可能的PHP警告/错误输出
                $jsonStart = strpos($content, '{');
                $arrayStart = strpos($content, '[');

                if ($jsonStart !== false && ($arrayStart === false || $jsonStart < $arrayStart)) {
                    $cleanContent = substr($content, $jsonStart);
                    $response->setContent($cleanContent);
                } elseif ($arrayStart !== false) {
                    $cleanContent = substr($content, $arrayStart);
                    $response->setContent($cleanContent);
                }
            }

            return $response;
        } finally {
            // 恢复原始设置
            error_reporting($originalErrorReporting);
        }
    }
}
