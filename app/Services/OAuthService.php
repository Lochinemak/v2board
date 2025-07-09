<?php

namespace App\Services;

use App\Models\User;
use App\Utils\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OAuthService
{
    /**
     * Process OAuth user data and create/update user
     *
     * @param string $provider
     * @param array $oauthUserData
     * @return array
     */
    public function processOAuthUser($provider, $oauthUserData)
    {
        DB::beginTransaction();
        
        try {
            // Validate OAuth user data
            if (empty($oauthUserData['id'])) {
                throw new \Exception('OAuth user ID is required');
            }

            // Check if user already exists with this OAuth provider
            $user = User::findByOAuth($provider, $oauthUserData['id']);
            
            if ($user) {
                // Update existing OAuth user
                $user = $this->updateOAuthUser($user, $oauthUserData);
            } else {
                // Check if user exists with same email
                $existingUser = null;
                if (!empty($oauthUserData['email'])) {
                    $existingUser = User::where('email', $oauthUserData['email'])->first();
                }
                
                if ($existingUser) {
                    // Bind OAuth to existing account
                    $user = $this->bindOAuthToExistingUser($existingUser, $provider, $oauthUserData);
                } else {
                    // Create new user from OAuth
                    $user = $this->createUserFromOAuth($provider, $oauthUserData);
                }
            }

            // Check if user is banned
            if ($user->banned) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('Your account has been suspended')
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'user' => $user
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OAuth processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'OAuth processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing OAuth user
     *
     * @param User $user
     * @param array $oauthUserData
     * @return User
     */
    private function updateOAuthUser(User $user, array $oauthUserData)
    {
        // Update OAuth-related fields
        $user->oauth_name = $oauthUserData['name'] ?? $oauthUserData['nickname'] ?? $user->oauth_name;
        $user->oauth_avatar = $oauthUserData['avatar'] ?? $user->oauth_avatar;
        
        // Update email if provided and different
        if (!empty($oauthUserData['email']) && $user->email !== $oauthUserData['email']) {
            // Check if email is already taken by another user
            $emailExists = User::where('email', $oauthUserData['email'])
                              ->where('id', '!=', $user->id)
                              ->exists();
            
            if (!$emailExists) {
                $user->email = $oauthUserData['email'];
            }
        }
        
        $user->last_login_at = time();
        $user->save();
        
        return $user;
    }

    /**
     * Bind OAuth to existing user
     *
     * @param User $user
     * @param string $provider
     * @param array $oauthUserData
     * @return User
     */
    private function bindOAuthToExistingUser(User $user, $provider, array $oauthUserData)
    {
        // Check if user already has OAuth binding
        if ($user->hasOAuthBinding()) {
            throw new \Exception('User already has OAuth binding');
        }
        
        $user->bindOAuth($provider, $oauthUserData);
        $user->last_login_at = time();
        $user->save();
        
        return $user;
    }

    /**
     * Create new user from OAuth data
     *
     * @param string $provider
     * @param array $oauthUserData
     * @return User
     */
    private function createUserFromOAuth($provider, array $oauthUserData)
    {
        // Validate required data
        if (empty($oauthUserData['email'])) {
            throw new \Exception('Email is required for new user creation');
        }

        // Check if email already exists
        $existingUser = User::where('email', $oauthUserData['email'])->first();
        if ($existingUser) {
            throw new \Exception('Email already exists');
        }

        $user = User::createFromOAuth($provider, $oauthUserData);
        $user->save();
        
        // Apply default settings for new OAuth users
        $this->applyDefaultSettingsForNewUser($user);
        
        return $user;
    }

    /**
     * Apply default settings for new OAuth users
     *
     * @param User $user
     * @return void
     */
    private function applyDefaultSettingsForNewUser(User $user)
    {
        // Apply default plan if configured
        $defaultPlanId = config('v2board.register_plan_id');
        if ($defaultPlanId) {
            $user->plan_id = $defaultPlanId;
        }

        // Apply default commission settings
        $user->commission_type = config('v2board.commission_default_type', 0);
        if (config('v2board.commission_default_rate')) {
            $user->commission_rate = config('v2board.commission_default_rate');
        }

        // Set default transfer quota if configured
        $defaultTransfer = config('v2board.register_default_transfer');
        if ($defaultTransfer) {
            $user->transfer_enable = $defaultTransfer * 1024 * 1024 * 1024; // Convert GB to bytes
        }

        $user->save();
    }

    /**
     * Generate authentication data for OAuth user
     *
     * @param User $user
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function generateAuthData(User $user, $request)
    {
        $authService = new AuthService($user);
        return $authService->generateAuthData($request);
    }

    /**
     * Get supported OAuth providers
     *
     * @return array
     */
    public function getSupportedProviders()
    {
        $providers = [];
        
        // Linux.do
        if (config('services.linuxdo.client_id')) {
            $providers['linuxdo'] = [
                'name' => 'Linux.Do',
                'icon' => 'linux',
                'url' => '/api/v1/passport/oauth/linuxdo/redirect'
            ];
        }

        // GitHub
        // if (config('services.github.client_id')) {
        //     $providers['github'] = [
        //         'name' => 'GitHub',
        //         'icon' => 'github',
        //         'url' => '/api/v1/passport/oauth/github/redirect'
        //     ];
        // }

        // // Google
        // if (config('services.google.client_id')) {
        //     $providers['google'] = [
        //         'name' => 'Google',
        //         'icon' => 'google',
        //         'url' => '/api/v1/passport/oauth/google/redirect'
        //     ];
        // }

        // // Custom OAuth provider
        // if (config('services.oauth_provider.client_id')) {
        //     $providers['oauth_provider'] = [
        //         'name' => config('services.oauth_provider.name', 'OAuth Provider'),
        //         'icon' => 'oauth',
        //         'url' => '/api/v1/passport/oauth/oauth_provider/redirect'
        //     ];
        // }
        
        return $providers;
    }

    /**
     * Validate OAuth provider
     *
     * @param string $provider
     * @return bool
     */
    public function isValidProvider($provider)
    {
        $supportedProviders = array_keys($this->getSupportedProviders());
        return in_array($provider, $supportedProviders);
    }
}
