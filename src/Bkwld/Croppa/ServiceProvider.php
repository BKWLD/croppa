<?php

namespace Bkwld\Croppa;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        // Bind the Croppa URL generator and parser
        $this->app->singleton('Bkwld\Croppa\URL', function ($app) {
            return new URL($this->getConfig());
        });

        // Handle the request for an image, this cooridnates the main logic
        $this->app->singleton('Bkwld\Croppa\Handler', function ($app) {
            return new Handler(
                $app['Bkwld\Croppa\URL'],
                $app['Bkwld\Croppa\Storage'],
                $app['request'],
                $this->getConfig()
            );
        });

        // Interact with the disk
        $this->app->singleton('Bkwld\Croppa\Storage', function ($app) {
            return new Storage($app, $this->getConfig());
        });

        // API for use in apps
        $this->app->singleton('Bkwld\Croppa\Helpers', function ($app) {
            return new Helpers($app['Bkwld\Croppa\URL'], $app['Bkwld\Croppa\Storage'], $app['Bkwld\Croppa\Handler']);
        });

        // Register command to delte all crops
        $this->app->singleton('Bkwld\Croppa\Commands\Purge', function ($app) {
            return new Commands\Purge($app['Bkwld\Croppa\Storage']);
        });

        // Register all commadns
        $this->commands('Bkwld\Croppa\Commands\Purge');
    }

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([__DIR__.'/../../config/config.php' => config_path('croppa.php')], 'croppa');

        $this->app['router']
            ->get('{path}', 'Bkwld\Croppa\Handler@handle')
            ->where('path', $this->app['Bkwld\Croppa\URL']->routePattern());
    }

    /**
     * Get the configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->app->make('config')->get('croppa');

        // Use Laravel's encryption key if instructed to
        if (isset($config['signing_key']) && $config['signing_key'] === 'app.key') {
            $config['signing_key'] = $this->app->make('config')->get('app.key');
        }

        return $config;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'Bkwld\Croppa\URL',
            'Bkwld\Croppa\Handler',
            'Bkwld\Croppa\Storage',
            'Bkwld\Croppa\Helpers',
            'Bkwld\Croppa\Commands\Purge',
        ];
    }
}
