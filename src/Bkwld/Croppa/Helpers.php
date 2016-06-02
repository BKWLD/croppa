<?php namespace Bkwld\Croppa;

/**
 * The public API to Croppa.  It generally passes through requests to other
 * classes
 */
class Helpers {

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
	public function __construct(URL $url, Storage $storage, Handler $handler) {
		$this->url = $url;
		$this->storage = $storage;
		$this->handler = $handler;
	}

	/**
	 * Delete source image and all of it's crops
	 *
	 * @param string $url URL of src image
	 * @return void
	 * @see Bkwld\Croppa\Storage::deleteSrc()
	 * @see Bkwld\Croppa\Storage::deleteCrops()
	 */
	public function delete($url) {
		$path = $this->url->relativePath($url);
		$this->storage->deleteSrc($path);
		$this->storage->deleteCrops($path);
	}

	/**
	 * Delete just the crops, leave the source image
	 *
	 * @param string $url URL of src image
	 * @return void
	 * @see Bkwld\Croppa\Storage::deleteCrops()
	 */
	public function reset($url) {
		$path = $this->url->relativePath($url);
		$this->storage->deleteCrops($path);
	}

	/**
	 * Create an image tag rather than just the URL.  Accepts the same params as url()
	 *
	 * @param string $url URL of an image that should be cropped
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param array $options Additional Croppa options, passed as key/value pairs.  Like array('resize')
	 * @return string An HTML img tag for the new image
	 * @see Bkwld\Croppa\URL::generate()
	 */
	public function tag($url, $width = null, $height = null, $options = null) {
		return '<img src="' . $this->url->generate($url, $width, $height, $options) . '">';
	}

	/**
	 * Pass through URL requests to URL->generate().
	 *
	 * @param string $url URL of an image that should be cropped
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param array $options Additional Croppa options, passed as key/value pairs.  Like array('resize')
	 * @return string The new path to your thumbnail
	 * @see Bkwld\Croppa\URL::generate()
	 */
	public function url($url, $width = null, $height = null, $options = null) {
		return $this->url->generate($url, $width, $height, $options);
	}


	/**
	 * Render image
	 *
	 * @param string $url URL of an image that should be rendered
	 * @return string The new path to your thumbnail
	 * @see Bkwld\Croppa\URL::generate()
	 */
	public function render($url) {
		return $this->handler->render($url);
	}

}
