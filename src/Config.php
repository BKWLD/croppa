<?php

namespace Bkwld\Croppa;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Container\Container;

class Config
{
    protected Application|Container $app;

    public function __construct(Application|Container $app)
    {
        $this->app = $app;
    }

    /**
     * Get the configuration.
     *
     * @return array
     * @throws BindingResolutionException
     */
    public function get(): array
    {
        $config = $this->app->make('config')->get('croppa');

        // Use Laravel’s encryption key if instructed to.
        if (
            isset($config['signing_key']) &&
            $config['signing_key'] === 'app.key'
        ) {
            $config['signing_key'] = $this->app->make('config')->get('app.key');
        }

        return $config;
    }
}