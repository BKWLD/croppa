<?php

declare(strict_types=1);

namespace Bkwld\Croppa;

use Bkwld\Croppa\Commands\Purge;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class CroppaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the Croppa URL generator and parser.
        $this->app->singleton(URL::class, fn ($app): URL => new URL($this->getConfig()));

        // Handle the request for an image, this coordinates the main logic.
        $this->app->singleton(Handler::class, fn ($app): Handler => new Handler(
            $app[URL::class],
            $app[Storage::class],
            $app['request'],
            $this->getConfig()
        ));

        // Interact with the disk.
        $this->app->singleton(Storage::class, fn (): Storage => new Storage($this->getConfig()));

        // API for use in apps.
        $this->app->singleton(Helpers::class, fn ($app): Helpers => new Helpers($app[URL::class], $app[Storage::class], $app[Handler::class]));

        // Register command to delete all crops.
        $this->app->singleton(Purge::class, fn ($app): Purge => new Purge($app[Storage::class]));

        $this->commands(Purge::class);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'croppa');
        $this->publishes([__DIR__.'/../config/config.php' => config_path('croppa.php')], 'croppa-config');

        $this->app->make(Router::class)
            ->get('{path}', 'Bkwld\Croppa\Handler@handle')
            ->where('path', $this->app->make(URL::class)->routePattern());
    }

    /**
     * Get the configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->app->make(Repository::class)->get('croppa');

        // Use Laravel’s encryption key if instructed to.
        if (isset($config['signing_key']) && $config['signing_key'] === 'app.key') {
            $config['signing_key'] = $this->app->make(Repository::class)->get('app.key');
        }

        return $config;
    }
}
