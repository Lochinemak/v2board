<?php

namespace App\Providers;

use App\Socialite\LinuxDoProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class SocialiteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Socialite::extend('linuxdo', function ($app) {
            $config = $app['config']['services.linuxdo'];
            
            return Socialite::buildProvider(LinuxDoProvider::class, $config);
        });
    }
}
