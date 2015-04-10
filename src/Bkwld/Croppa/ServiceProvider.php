<?php namespace Bkwld\Croppa;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {

	/**
	 * Get the major Laravel version number
	 *
	 * @return integer 
	 */
	public function version() {
		$app = $this->app;
		return intval($app::VERSION);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Version specific registering
		if ($this->version() == 5) $this->registerLaravel5();

		// Bind the Croppa URL generator and parser
		$this->app->singleton('Bkwld\Croppa\URL', function($app) {
			return new URL($this->getConfig());
		});

		// Handle the request for an image, this cooridnates the main logic
		$this->app->singleton('Bkwld\Croppa\Handler', function($app) {
			return new Handler($app['Bkwld\Croppa\URL'], $app['Bkwld\Croppa\Storage']);
		});

		// Interact with the disk
		$this->app->singleton('Bkwld\Croppa\Storage', function($app) {
			return Storage::make($app, $this->getConfig());
		});

		// API for use in apps
		$this->app->singleton('Bkwld\Croppa\Helpers', function($app) {
			return new Helpers($app['Bkwld\Croppa\URL'], $app['Bkwld\Croppa\Storage']);
		});
	}

	/**
	 * Register specific logic for Laravel 5. Merges package config with user config
	 * 
	 * @return void
	 */
	public function registerLaravel5() {
		$this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'croppa');
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {

		// Version specific booting
		switch($this->version()) {
			case 4: $this->bootLaravel4(); break;
			case 5: $this->bootLaravel5(); break;
			default: throw new Exception('Unsupported Laravel version');
		}

		// Listen for Cropa style URLs, these are how Croppa gets triggered
		$this->app['router']
			->get('{path}', 'Bkwld\Croppa\Handler@handle')
			->where('path', $this->app['Bkwld\Croppa\URL']->routePattern());
	}

	/**
	 * Boot specific logic for Laravel 4. Tells Laravel about the package for auto 
	 * namespacing of config files
	 * 
	 * @return void
	 */
	public function bootLaravel4() {
		$this->package('bkwld/croppa');
	}

	/**
	 * Boot specific logic for Laravel 5. Registers the config file for publishing 
	 * to app directory
	 * 
	 * @return void
	 */
	public function bootLaravel5() {
		$this->publishes([
			__DIR__.'/../../config/config.php' => config_path('croppa.php')
		], 'croppa');
	}

	/**
	 * Get the configuration, which is keyed differently in L5 vs l4
	 *
	 * @return array 
	 */
	public function getConfig() {
		$key = $this->version() == 5 ? 'croppa' : 'croppa::config';
		return $this->app->make('config')->get($key);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return [
			'Bkwld\Croppa\URL',
			'Bkwld\Croppa\Handler',
			'Bkwld\Croppa\Storage',
			'Bkwld\Croppa\Helpers',
		];
	}
}
