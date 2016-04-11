<?php

namespace Kavinsky\Vermut;

use Illuminate\Support\ServiceProvider;

class VermutServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('vermut.marker', function ($app) {
            return new Marker($app['redis']);
        });

        $this->app->singleton('vermut.analyzer', function ($app) {
            return new Analyzer($app['redis']);

        });

        $this->app->singleton('vermut.marker', function ($app) {

        });
    }
}
