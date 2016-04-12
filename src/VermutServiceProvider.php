<?php

namespace Kavinsky\Vermut;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class VermutServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boots the provider
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/vermut.php' => config_path('vermut.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vermut.php', 'vermut');

        $this->app->singleton('vermut', function ($app) {
            return new Vermut($app['redis'], $app['config']['vermut']);
        });

        // adding terminating callback
        $this->app->terminating(function (ApplicationContract $app) {
            $app['vermut']->sentUpstreamOperations();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'vermut'
        ];
    }
}
