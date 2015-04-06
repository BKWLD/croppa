<?php namespace Bkwld\Croppa;

/**
 * Handle a Croppa-style request, forwarding the actual work onto other classes.
 */
class Handler {

	/**
	 * @var Bkwld\Croppa\Storage
	 */
	private $storage;

	/**
	 * @var Bkwld\Croppa\URL
	 */
	private $url;

	/**
	 * Dependency injection
	 *
	 * @param Bkwld\Croppa\URL $url
	 * @param Bkwld\Croppa\Storage $storage
	 */
	public function __construct(URL $url, Storage $storage) {
		$this->url = $url;
		$this->storage = $storage;
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
		if ($this->storage->cropsAreRemote()) {
			// WILL NEED TO ADD A CONFIG TO SET THE PREFIX URL FOR THIS, LIKE UPCHUCK
		} 

		// Build a new image using fetched image data
		$image = new Image(
			$this->storage->getSrc($path), 
			$this->url->phpThumbConfig($options)
		);

		// Process the image
		
		
		// Write the image to the crop dir
		
		// Render the image to the browser
		return $image->show();



		// $image = $croppa->generate($path);
		// return \Response::stream(function() use ($image) {
		// 	return $image->show();
		// });

	}

}