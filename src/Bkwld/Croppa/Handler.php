<?php namespace Bkwld\Croppa;

// Deps
use Illuminate\Routing\Controller;

/**
 * Handle a Croppa-style request, forwarding the actual work onto other classes.
 */
class Handler extends Controller {

	/**
	 * Handles a Croppa style route
	 *
	 * @param string $path The `Request::path()`
	 * @return Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function handle($path) {
		dd($path);
		// $image = $croppa->generate($path);
		// return \Response::stream(function() use ($image) {
		// 	return $image->show();
		// });

	}

}