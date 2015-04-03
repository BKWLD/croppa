<?php namespace Bkwld\Croppa;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {

	/**
	 * Get the major Laravel version number
	 *
	 * @return integer 
	 */
	public function version() {
		return intval($this->app::VERSION);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Version specific registering
		if ($this->version() == 5) $this->registerLaravel5();

		// Bind a new singleton instance of Croppa to the app
		$this->app->singleton('croppa', function($app) {

			// Inject dependencies
			return new Croppa(array_merge($app->make('config')->get('croppa::config'), [
				'host' => '//'.$app->make('request')->getHttpHost(),
				'public' => $app->make('path.public'),
			]));
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
		$croppa = $this->app['croppa'];
		$this->app->make('router')->get('{path}', function($path) use ($croppa) {
			$image = $croppa->generate($path);
			return \Response::stream(function() use ($image) {
				return $image->show();
			});
		})->where('path', $croppa->directoryPattern());
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
		], 'config');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return ['croppa'];
	}
}
