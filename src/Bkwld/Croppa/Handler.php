<?php namespace Bkwld\Croppa;

/**
 * Handle a Croppa-style request, forwarding the actual work onto other classes.
 */
class Handler {

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

		// Parse the path.  In the case there is an error (the pattern on the route 
		// SHOULD have caught all errors with the pattern) just return
		if (!$params = $this->url->parse($request)) return;
		list($path, $width, $height, $options) = $params;

		// If the crops_dir is a remote disk, check if the path exists on it and redirect

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