<?php

namespace Kavinsky\Vermut;

use Illuminate\Support\ServiceProvider;

class VermutServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('vermut', function ($app) {
            return new Vermut($app['redis'], $app['config']['vermut']);
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
