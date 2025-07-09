<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'uuid' => \App\Utils\Helper::guid(true),
        'token' => \App\Utils\Helper::guid(),
        'balance' => 0,
        'commission_type' => 0,
        'commission_balance' => 0,
        't' => 0,
        'u' => 0,
        'd' => 0,
        'transfer_enable' => 0,
        'banned' => 0,
        'is_admin' => 0,
        'is_staff' => 0,
        'last_login_at' => time(),
        'created_at' => time(),
        'updated_at' => time(),
        // OAuth fields
        'oauth_provider' => null,
        'oauth_provider_id' => null,
        'oauth_avatar' => null,
        'oauth_name' => null,
        'is_oauth_user' => false,
    ];
});
