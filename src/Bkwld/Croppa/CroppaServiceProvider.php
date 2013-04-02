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
		
		// Add dependencies to the Laravel autoloader
		Autoloader::map(array(
		    'Croppa' => Bundle::path('croppa').'library/Croppa.php',
		    'Croppa\Exception' => Bundle::path('croppa').'library/Exception.php',
		    'PhpThumbFactory' => Bundle::path('croppa').'vendor/PHPThumb/src/ThumbLib.inc.php',
		));

		// Pass along the Config data so Croppa is decoupled from Laravel
		Croppa::config(Config::get('croppa::croppa'));

		// Subscribe to 404 events, these are how Croppa gets triggered
		Event::listen('404', function() {
			
			// Increase memory limit, cause some images require a lot
			// too resize
			ini_set('memory_limit', '128M');
			
			// Pass Croppa the current URL
			Croppa::handle_404(Request::uri());
		});
		
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
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