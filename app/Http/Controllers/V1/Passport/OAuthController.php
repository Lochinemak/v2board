<?php

namespace App\Http\Controllers\V1\Passport;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OAuthService;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    protected $oauthService;

    public function __construct(OAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * Redirect to OAuth provider
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect($provider)
    {
        try {
            // Validate provider
            if (!$this->oauthService->isValidProvider($provider)) {
                return response()->json([
                    'message' => 'Unsupported OAuth provider'
                ], 400);
            }

            // Store the intended redirect URL in session if provided
            $redirectUrl = request('redirect');
            if ($redirectUrl) {
                session(['oauth_redirect' => $redirectUrl]);
            }

            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'OAuth redirect failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle OAuth callback
     *
     * @param string $provider
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function callback($provider, Request $request)
    {
        try {
            // Validate provider
            if (!$this->oauthService->isValidProvider($provider)) {
                return $this->redirectWithError('Unsupported OAuth provider');
            }

            // Get user from OAuth provider
            $oauthUser = Socialite::driver($provider)->user();

            if (!$oauthUser) {
                return $this->redirectWithError('Failed to get user information from OAuth provider');
            }

            // Prepare OAuth user data
            $oauthUserData = [
                'id' => $oauthUser->getId(),
                'email' => $oauthUser->getEmail(),
                'name' => $oauthUser->getName(),
                'nickname' => $oauthUser->getNickname(),
                'avatar' => $oauthUser->getAvatar()
            ];

            // Process OAuth login
            $result = $this->oauthService->processOAuthUser($provider, $oauthUserData);

            if ($result['success']) {
                // Generate auth data
                $authData = $this->oauthService->generateAuthData($result['user'], $request);

                // Create a temporary token for V2Board's auth flow (similar to email login)
                $code = \App\Utils\Helper::guid();
                $key = \App\Utils\CacheKey::get('TEMP_TOKEN', $code);
                \Illuminate\Support\Facades\Cache::put($key, $result['user']->id, 300);

                // Get redirect URL from session or default to dashboard
                $redirectTarget = session('oauth_redirect', 'dashboard');
                session()->forget('oauth_redirect');

                // Handle admin OAuth flow
                if ($redirectTarget === 'admin') {
                    // For admin OAuth, we need to check if user is admin and redirect appropriately
                    if ($result['user']->is_admin) {
                        // Generate admin auth token and redirect to admin panel
                        $adminAuthData = $this->oauthService->generateAuthData($result['user'], $request);

                        // Get admin secure path
                        $securePath = \App\Utils\AdminPathGenerator::getCurrentPath();

                        // Create admin redirect URL with auth data
                        $redirectUrl = '/' . $securePath . '#/login?oauth_success=1&auth_data=' . urlencode($adminAuthData['auth_data']) . '&token=' . urlencode($adminAuthData['token']);
                    } else {
                        // User is not admin, redirect with error
                        return $this->redirectWithError('您没有管理员权限');
                    }
                } else {
                    // Follow V2Board's standard auth flow pattern for regular users
                    // Add access parameter for anti-fingerprinting
                    $accessKey = substr(md5(config('app.key')), 0, 8);
                    $redirectUrl = '/?access=' . $accessKey . '#/login?verify=' . $code . '&redirect=' . $redirectTarget;
                }

                // Build clean URL without preserving current query parameters
                if (config('v2board.app_url')) {
                    $fullUrl = config('v2board.app_url') . $redirectUrl;
                } else {
                    $scheme = $request->isSecure() ? 'https' : 'http';
                    $host = $request->getHost();
                    $port = $request->getPort();

                    $baseUrl = $scheme . '://' . $host;
                    if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
                        $baseUrl .= ':' . $port;
                    }

                    $fullUrl = $baseUrl . $redirectUrl;
                }

                return redirect($fullUrl);
            } else {
                return $this->redirectWithError($result['message']);
            }

        } catch (\Exception $e) {
            return $this->redirectWithError('OAuth callback failed: ' . $e->getMessage());
        }
    }

    /**
     * Redirect with error message
     *
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectWithError($message)
    {
        // Check if this was an admin OAuth attempt
        $redirectTarget = session('oauth_redirect', 'dashboard');

        // Clear any stored redirect URL
        session()->forget('oauth_redirect');

        if ($redirectTarget === 'admin') {
            // Get admin secure path
            $securePath = \App\Utils\AdminPathGenerator::getCurrentPath();

            // Redirect to admin login with error
            $redirectUrl = '/' . $securePath . '#/login?oauth_error=' . urlencode($message);
        } else {
            // Redirect to regular login with error
            // Add access parameter for anti-fingerprinting
            $accessKey = substr(md5(config('app.key')), 0, 8);
            $redirectUrl = '/?access=' . $accessKey . '#/login?oauth_error=' . urlencode($message);
        }

        // Build clean URL without preserving current query parameters
        if (config('v2board.app_url')) {
            $fullUrl = config('v2board.app_url') . $redirectUrl;
        } else {
            $scheme = request()->isSecure() ? 'https' : 'http';
            $host = request()->getHost();
            $port = request()->getPort();

            $baseUrl = $scheme . '://' . $host;
            if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
                $baseUrl .= ':' . $port;
            }

            $fullUrl = $baseUrl . $redirectUrl;
        }

        return redirect($fullUrl);
    }




    /**
     * Get OAuth providers configuration for frontend
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProviders()
    {
        return response()->json([
            'data' => $this->oauthService->getSupportedProviders()
        ]);
    }
}
