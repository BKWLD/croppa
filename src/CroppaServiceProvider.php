<?php

namespace Bkwld\Croppa;

use Bkwld\Croppa\Commands\Purge;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

class CroppaServiceProvider extends ServiceProvider
{
    protected Config $croppaConfig;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->croppaConfig = new Config($this->app);
    }

    public function register()
    {
        // Bind the Croppa URL generator and parser.
        $this->app->singleton(URL::class, function ($app) {
            return new URL($this->getConfig());
        });

        // Handle the request for an image, this coordinates the main logic.
        $this->app->singleton(Handler::class, function ($app) {
            return new Handler(
                $app[URL::class],
                $app[Storage::class],
                $app['request'],
                $this->getConfig()
            );
        });

        // Interact with the disk.
        $this->app->singleton(Storage::class, function () {
            return new Storage($this->getConfig());
        });

        // API for use in apps.
        $this->app->singleton(Helpers::class, function ($app) {
            return new Helpers($app[URL::class], $app[Storage::class], $app[Handler::class]);
        });

        // Register command to delete all crops.
        $this->app->singleton(Purge::class, function ($app) {
            return new Purge($app[Storage::class]);
        });

        $this->commands(Purge::class);
    }

    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'croppa');
        $this->publishes([__DIR__.'/../config/config.php' => config_path('croppa.php')], 'croppa-config');

        $this->app['router']
            ->get('{path}', 'Bkwld\Croppa\Handler@handle')
            ->where('path', $this->app[URL::class]->routePattern());
    }

    /**
     * Get the configuration.
     *
     * @return array
     * @throws BindingResolutionException
     */
    protected function getConfig(): array
    {
        return $this->croppaConfig->get();
    }
}
