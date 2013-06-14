<?php namespace Bkwld\Croppa;

use Illuminate\Support\ServiceProvider;

class CroppaServiceProvider extends ServiceProvider {

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
	public function boot()
	{
		$this->package('bkwld/croppa');

		// Pass along the Config data so Croppa is decoupled from Laravel
		Croppa::config(array_merge($this->app->make('config')->get('croppa::config'), array(
			'host' => $this->app->make('request')->root(),
		)));

		// Listen for Cropa style URls, these are how Croppa gets triggered
		$this->app->make('router')->get('{path}', function($path) {
			Croppa::generate($path);
		})->where('path', Croppa::PATTERN);
		
		// Make it possible to access outside of namespace
		class_alias('Bkwld\Croppa\Croppa', 'Croppa');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}