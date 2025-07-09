<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $oauthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oauthService = new OAuthService();
    }

    /**
     * Test OAuth providers endpoint
     */
    public function test_oauth_providers_endpoint()
    {
        $response = $this->get('/api/v1/passport/oauth/providers');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data'
                ]);
    }

    /**
     * Test OAuth redirect endpoint
     */
    public function test_oauth_redirect_with_invalid_provider()
    {
        $response = $this->get('/api/v1/passport/oauth/invalid_provider/redirect');
        
        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Unsupported OAuth provider'
                ]);
    }

    /**
     * Test OAuth user creation from provider data
     */
    public function test_create_user_from_oauth()
    {
        $oauthUserData = [
            'id' => '12345',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'nickname' => 'testuser',
            'avatar' => 'https://example.com/avatar.jpg'
        ];

        $result = $this->oauthService->processOAuthUser('github', $oauthUserData);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(User::class, $result['user']);
        
        $user = $result['user'];
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('github', $user->oauth_provider);
        $this->assertEquals('12345', $user->oauth_provider_id);
        $this->assertEquals('Test User', $user->oauth_name);
        $this->assertTrue($user->is_oauth_user);
    }

    /**
     * Test OAuth user binding to existing account
     */
    public function test_bind_oauth_to_existing_user()
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'test@example.com',
            'oauth_provider' => null,
            'oauth_provider_id' => null,
            'is_oauth_user' => false
        ]);

        $oauthUserData = [
            'id' => '12345',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'nickname' => 'testuser',
            'avatar' => 'https://example.com/avatar.jpg'
        ];

        $result = $this->oauthService->processOAuthUser('github', $oauthUserData);

        $this->assertTrue($result['success']);
        
        $user = $result['user'];
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals('github', $user->oauth_provider);
        $this->assertEquals('12345', $user->oauth_provider_id);
        $this->assertEquals('Test User', $user->oauth_name);
    }

    /**
     * Test OAuth user update on subsequent login
     */
    public function test_update_oauth_user_on_login()
    {
        // Create OAuth user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'oauth_provider' => 'github',
            'oauth_provider_id' => '12345',
            'oauth_name' => 'Old Name',
            'oauth_avatar' => 'old_avatar.jpg',
            'is_oauth_user' => true
        ]);

        $oauthUserData = [
            'id' => '12345',
            'email' => 'test@example.com',
            'name' => 'Updated Name',
            'nickname' => 'updateduser',
            'avatar' => 'https://example.com/new_avatar.jpg'
        ];

        $result = $this->oauthService->processOAuthUser('github', $oauthUserData);

        $this->assertTrue($result['success']);
        
        $updatedUser = $result['user'];
        $this->assertEquals($user->id, $updatedUser->id);
        $this->assertEquals('Updated Name', $updatedUser->oauth_name);
        $this->assertEquals('https://example.com/new_avatar.jpg', $updatedUser->oauth_avatar);
    }

    /**
     * Test OAuth with banned user
     */
    public function test_oauth_with_banned_user()
    {
        $user = User::factory()->create([
            'email' => 'banned@example.com',
            'oauth_provider' => 'github',
            'oauth_provider_id' => '54321',
            'banned' => 1,
            'is_oauth_user' => true
        ]);

        $oauthUserData = [
            'id' => '54321',
            'email' => 'banned@example.com',
            'name' => 'Banned User',
            'nickname' => 'banneduser',
            'avatar' => 'https://example.com/avatar.jpg'
        ];

        $result = $this->oauthService->processOAuthUser('github', $oauthUserData);

        $this->assertFalse($result['success']);
        $this->assertStringContains('suspended', $result['message']);
    }

    /**
     * Test OAuth with missing required data
     */
    public function test_oauth_with_missing_data()
    {
        $oauthUserData = [
            // Missing 'id' field
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];

        $result = $this->oauthService->processOAuthUser('github', $oauthUserData);

        $this->assertFalse($result['success']);
        $this->assertStringContains('OAuth user ID is required', $result['message']);
    }

    /**
     * Test OAuth with missing email for new user
     */
    public function test_oauth_new_user_without_email()
    {
        $oauthUserData = [
            'id' => '12345',
            // Missing email
            'name' => 'Test User',
            'nickname' => 'testuser'
        ];

        $result = $this->oauthService->processOAuthUser('github', $oauthUserData);

        $this->assertFalse($result['success']);
        $this->assertStringContains('Email is required', $result['message']);
    }

    /**
     * Test supported providers
     */
    public function test_get_supported_providers()
    {
        $providers = $this->oauthService->getSupportedProviders();
        
        $this->assertIsArray($providers);
        
        // Test structure if providers are configured
        foreach ($providers as $key => $provider) {
            $this->assertArrayHasKey('name', $provider);
            $this->assertArrayHasKey('icon', $provider);
            $this->assertArrayHasKey('url', $provider);
        }
    }

    /**
     * Test provider validation
     */
    public function test_provider_validation()
    {
        $this->assertFalse($this->oauthService->isValidProvider('invalid_provider'));
        
        // These will be true only if configured in services.php
        if (config('services.github.client_id')) {
            $this->assertTrue($this->oauthService->isValidProvider('github'));
        }
        
        if (config('services.google.client_id')) {
            $this->assertTrue($this->oauthService->isValidProvider('google'));
        }
    }
}
