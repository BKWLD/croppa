<?php namespace Bkwld\Croppa;

use Illuminate\Support\ServiceProvider;

class ServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->package('bkwld/croppa');

		// Inject config data into new instance of Croppa
		$croppa = new Croppa(array_merge($this->app->make('config')->get('croppa::config'), array(
			'host' => $this->app->make('request')->root(),
			'public' => $this->app->make('path.public'),
		)));

		// Bind Croppa to the app
		$this->app->instance('croppa', $croppa);

		// Listen for Cropa style URLs, these are how Croppa gets triggered
		$this->app->make('router')->get('{path}', function($path) use ($croppa) {
			$croppa->generate($path);
		})->where('path', $croppa->pattern());
		
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array('croppa');
	}

}