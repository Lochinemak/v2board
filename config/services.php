<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_V2BOARD_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for third-party OAuth providers
    |
    */

    // GitHub OAuth
    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('APP_URL', 'http://localhost') . '/api/v1/passport/oauth/github/callback',
    ],

    // Google OAuth
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL', 'http://localhost') . '/api/v1/passport/oauth/google/callback',
    ],

    // Linux.do OAuth配置
    'linuxdo' => [
        'client_id' => env('LINUXDO_CLIENT_ID'),
        'client_secret' => env('LINUXDO_CLIENT_SECRET'),
        'redirect' => env('APP_URL', 'http://localhost') . '/api/v1/passport/oauth/linuxdo/callback',
    ],

    // 通用OAuth配置 - 你可以根据实际的第三方服务修改这个配置
    'oauth_provider' => [
        'client_id' => env('OAUTH_CLIENT_ID'),
        'client_secret' => env('OAUTH_CLIENT_SECRET'),
        'redirect' => env('APP_URL', 'http://localhost') . '/api/v1/passport/oauth/oauth_provider/callback',
        'name' => env('OAUTH_PROVIDER_NAME', 'OAuth Provider'),
    ],

];
