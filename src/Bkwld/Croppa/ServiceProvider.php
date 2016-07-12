<?php namespace Bkwld\Croppa;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {

	/**
	 * Get the major Laravel version number
	 *
	 * @return integer
	 */
	public function version() {
		$app = $this->app;
		if (defined(get_class($app).'::VERSION')) {
			return intval($app::VERSION);
		}

		if (is_callable([$app, 'version'])) {
			preg_match('/(\((\d+\.\d+\.\d+)\))/', $app->version(), $v);
			if (isset($v[2])) {
				return -intval($v[2]);
			}
		}

		return null;
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Version specific registering
		if (abs($this->version()) == 5) {
			$this->registerLaravel5Lumen();
		}

		// Bind the Croppa URL generator and parser
		$this->app->singleton('Bkwld\Croppa\URL', function($app) {
			return new URL($this->getConfig());
		});

		// Handle the request for an image, this cooridnates the main logic
		$this->app->singleton('Bkwld\Croppa\Handler', function($app) {
			return new Handler($app['Bkwld\Croppa\URL'],
				$app['Bkwld\Croppa\Storage'],
				$app['request'],
				$this->getConfig());
		});

		// Interact with the disk
		$this->app->singleton('Bkwld\Croppa\Storage', function($app) {
			return Storage::make($app, $this->getConfig());
		});

		// API for use in apps
		$this->app->singleton('Bkwld\Croppa\Helpers', function($app) {
			return new Helpers($app['Bkwld\Croppa\URL'], $app['Bkwld\Croppa\Storage'], $app['Bkwld\Croppa\Handler']);
		});

		// Register command to delte all crops
		$this->app->singleton('Bkwld\Croppa\Commands\Purge', function($app) {
			return new Commands\Purge($app['Bkwld\Croppa\Storage']);
		});

		// Register all commadns
		$this->commands('Bkwld\Croppa\Commands\Purge');
	}

	/**
	 * Register specific logic for Laravel/Lumen 5. Merges package config with user config
	 *
	 * @return void
	 */
	public function registerLaravel5Lumen() {
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
			case -5: $this->bootLumen(); break;
			default: throw new Exception('Unsupported Laravel version');
		}

		// Listen for Cropa style URLs, these are how Croppa gets triggered
		if ($this->version() > 0) { // Laravel
			$this->app['router']
				->get('{path}', 'Bkwld\Croppa\Handler@handle')
				->where('path', $this->app['Bkwld\Croppa\URL']->routePattern());
		} else { // Lumen
			$this->app->get('{path:'.$this->app['Bkwld\Croppa\URL']->routePattern().'}', [
				'uses' => 'Bkwld\Croppa\Handler@handle',
			]);
		}
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
	 * Boot specific logic for Lumen. Load custom croppa config file
	 *
	 * @return void
	 */
	public function bootLumen() {
		$this->app->configure('croppa');
	}

	/**
	 * Get the configuration, which is keyed differently in L5 vs l4
	 *
	 * @return array
	 */
	public function getConfig() {
		$key = abs($this->version()) == 5 ? 'croppa' : 'croppa::config';

		$config = $this->app->make('config')->get($key);

		// Use Laravel's encryption key if instructed to
		if (isset($config['signing_key']) && $config['signing_key'] == 'app.key') {
			$config['signing_key'] = $this->app->make('config')->get('app.key');
		}

		return $config;
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
			'Bkwld\Croppa\Commands\Purge',
		];
	}
}
