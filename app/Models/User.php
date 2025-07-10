<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'v2_user';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'is_oauth_user' => 'boolean'
    ];

    /**
     * Find user by OAuth provider and provider ID
     *
     * @param string $provider
     * @param string $providerId
     * @return User|null
     */
    public static function findByOAuth($provider, $providerId)
    {
        return static::where('oauth_provider', $provider)
                    ->where('oauth_provider_id', $providerId)
                    ->first();
    }

    /**
     * Create user from OAuth provider data
     *
     * @param string $provider
     * @param array $userData
     * @return User
     */
    public static function createFromOAuth($provider, $userData)
    {
        $user = new static();
        $user->email = $userData['email'] ?? '';
        $user->oauth_provider = $provider;
        $user->oauth_provider_id = $userData['id'];
        $user->oauth_name = $userData['nickname'] ?? $userData['name'] ?? '';
        $user->oauth_avatar = $userData['avatar'] ?? '';
        $user->is_oauth_user = true;
        $user->uuid = \App\Utils\Helper::guid(true);
        $user->token = \App\Utils\Helper::guid();
        $user->password = password_hash(\App\Utils\Helper::guid(), PASSWORD_DEFAULT); // Random password
        $user->last_login_at = time();

        return $user;
    }

    /**
     * Check if user has OAuth binding
     *
     * @return bool
     */
    public function hasOAuthBinding()
    {
        return !empty($this->oauth_provider) && !empty($this->oauth_provider_id);
    }

    /**
     * Bind OAuth account to existing user
     *
     * @param string $provider
     * @param array $userData
     * @return void
     */
    public function bindOAuth($provider, $userData)
    {
        $this->oauth_provider = $provider;
        $this->oauth_provider_id = $userData['id'];
        $this->oauth_name = $userData['nickname'] ?? $userData['name'] ?? '';
        $this->oauth_avatar = $userData['avatar'] ?? '';
    }
}
