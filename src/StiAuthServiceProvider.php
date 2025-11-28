<?php

namespace Sti\StiAuth;

use Sti\StiAuth\Auth\RemoteGuard;
use Illuminate\Support\Facades\Auth;
use Sti\StiAuth\Services\AuthClient;
use Illuminate\Support\ServiceProvider;
use Sti\StiAuth\Auth\RemoteUserProvider;
use Sti\StiAuth\Middleware\VerifyApiToken;

class StiAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sti-auth.php', 'lara-auth');

        $this->app->singleton(AuthClient::class, function () {
            return new AuthClient(config('lara-auth'));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/sti-auth.php' => config_path('sti-auth.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__ . '/../routes/demo.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sti-auth');

        app('router')->aliasMiddleware('auth.api', VerifyApiToken::class);

                // UserProvider registrieren
        Auth::provider('remote', function ($app, array $config) {
            return new RemoteUserProvider();
        });

        // Guard registrieren
        Auth::extend('remote', function ($app, $name, array $config) {
            return new RemoteGuard(
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );
        });
    }
}
