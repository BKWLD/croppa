<?php namespace Bkwld\Croppa;

// Deps
use Illuminate\Routing\Controller;
use Response;

/**
 * Handle the request and pass onto execute
 */
class Handler extends Controller {

	/**
	 * Handles a Croppa style route
	 *
	 * @param string $request The `Request::path()`
	 * @throws Exception 
	 * @return Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function handle($request) {
		$image = app('croppa')->generate($request);
		return Response::stream(function() use ($image) {
			return $image->show();
		});
	}

}