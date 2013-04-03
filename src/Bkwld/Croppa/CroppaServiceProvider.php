<?php namespace Bkwld\Croppa;

use Illuminate\Support\ServiceProvider;
use \App;
use \Request;
use \Config;

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
		Croppa::config(array_merge(Config::get('croppa::config'), array(
			'host' => Request::root(),
		)));

		// Subscribe to 404 events, these are how Croppa gets triggered
		App::missing(function($e) {

			// Increase memory limit, cause some images require a lot
			// too resize
			ini_set('memory_limit', '128M');
			
			// Pass Croppa the current URL
			Croppa::handle_404(Request::path());
		});
		
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