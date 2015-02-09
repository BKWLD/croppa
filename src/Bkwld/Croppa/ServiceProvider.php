<?php namespace Bkwld\Croppa;

// Dependencies
use Illuminate\Support\Facades\Response;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		// Bind a new singleton instance of Croppa to the app
		$this->app->singleton('croppa', function($app) {

			// Inject dependencies
			return new Croppa(array_merge(array(
				'host' => '//'.$app->make('request')->getHttpHost(),
				'public' => $app->make('path.public'),
			), $app->make('config')->get('croppa::config')));
		});
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->package('bkwld/croppa');

		// Listen for Cropa style URLs, these are how Croppa gets triggered
		$croppa = $this->app['croppa'];
		$this->app->make('router')->get('{path}', function($path) use ($croppa) {
			$image = $croppa->generate($path);
			return Response::stream(function() use ($image) {
				return $image->show();
			});
		})->where('path', $croppa->directoryPattern());
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
