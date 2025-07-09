<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $authorization = $request->input('auth_data') ?? $request->header('authorization');
        if (!$authorization) abort(403, 'Access denied');

        $user = AuthService::decryptAuthData($authorization);
        if (!$user || !$user['is_admin']) abort(403, 'Access denied');
        $request->merge([
            'user' => $user
        ]);
        return $next($request);
    }
}
