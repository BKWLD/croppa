<?php namespace Bkwld\Croppa;

// Deps
use Illuminate\Routing\Controller;

/**
 * Handle a Croppa-style request, forwarding the actual work onto other classes.
 */
class Handler extends Controller {

	/**
	 * @var Bkwld\Croppa\URL
	 */
	private $url;

	/**
	 * Dependency injection
	 *
	 * @param Bkwld\Croppa\URL $url
	 */
	public function __construct(URL $url) {
		$this->url = $url;
	}

	/**
	 * Handles a Croppa style route
	 *
	 * @param string $request The `Request::path()`
	 * @return Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function handle($request) {

		// Parse the path
		list($path, $width, $height, $options) = $this->url->parse($request);

		// Look for the src image
		print_r($this->url->parse($request));

		// Crop the image
		
		// Write the image to the crop dir
		
		// Render the image to the browser




		// $image = $croppa->generate($path);
		// return \Response::stream(function() use ($image) {
		// 	return $image->show();
		// });

	}

}