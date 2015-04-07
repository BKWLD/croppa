<?php namespace Bkwld\Croppa;

// Deps
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

		// Get crop path relative to it's dir
		$crop_path = $this->url->relativePath($request);

		// If the crops_dir is a remote disk, check if the path exists on it and redirect
		if ($this->storage->cropsAreRemote() 
			&& $this->storage->cropExists($crop_path)) {
			return new RedirectResponse($this->storage->cropUrl($crop_path), 301);
		}

		// Parse the path.  In the case there is an error (the pattern on the route 
		// SHOULD have caught all errors with the pattern) just return
		if (!$params = $this->url->parse($request)) return;
		list($path, $width, $height, $options) = $params;

		// Check if there are too many crops already
		// if ($this->storage->tooManyCrops()) throw new Exception('Croppa: Max crops reached');

		// Increase memory limit, cause some images require a lot to resize
		ini_set('memory_limit', '128M');

		// Build a new image using fetched image data
		$image = new Image(
			$this->storage->readSrc($path), 
			$this->url->phpThumbConfig($options)
		);
		
		// Process the image, get the image data back, and write to disk
		$file = $this->storage->writeCrop($crop_path, 
			$image->process($width, $height, $options)->get()
		);

		// Redirect to remote crops or render the image
		if (preg_match('#^https?://#', $file)) return new RedirectResponse($file, 301);
		else return new BinaryFileResponse($file, 200, [
			'Content-Type' => $this->getContentType($path),
		]);

	}

	/**
	 * Symfony kept returning the MIME-type of my testing jpgs as PNGs, so
	 * determining it explicitly via looking at the path name.
	 *
	 * @param string $path 
	 * @return string 
	 */
	public function getContentType($path) {
		switch(pathinfo($path, PATHINFO_EXTENSION)) {
			case 'jpeg':
			case 'jpg': return 'image/jpeg';
			case 'gif': return 'image/gif';
			case 'png': return 'image/png';
		}
	}

}