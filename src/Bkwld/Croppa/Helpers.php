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
	public function __construct(URL $url, Storage $storage) {
		$this->url = $url;
		$this->storage = $storage;
	}

	/**
	 * Delete source image and all of it's crops
	 *
	 * @param string $url 
	 * @return void 
	 * @see Bkwld\Croppa\Storage::deleteSrc()
	 */
	public function delete($url) {
		return $this->storage->deleteSrc($this->url->relativePath($url));
	}

	/**
	 * Delete just the crops, leave the source image
	 *
	 * @param string $url 
	 * @return void 
	 * @see Bkwld\Croppa\Storage::deleteCrops()
	 */
	public function reset($url) {
		return $this->storage->deleteCrops($this->url->relativePath($url));
	}

	/**
	 * Create an image tag rather than just the URL.  Accepts the same params as url()
	 *
	 * @param string $url URL of an image that should be cropped
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param array $options Addtional Croppa options, passed as key/value pairs.  Like array('resize')
	 * @return string An HTML img tag for the new image
	 * @see Bkwld\Croppa\URL::generate()
	 */
	public function tag($url, $width = null, $height = null, $options = null) {
		return '<img src="'.$this->url->generate($url, $width, $height, $options).'">';
	}

	/**
	 * Pass through URL requrests to URL->generate().
	 *
	 * @param string $url URL of an image that should be cropped
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param array $options Addtional Croppa options, passed as key/value pairs.  Like array('resize')
	 * @return string The new path to your thumbnail
	 * @see Bkwld\Croppa\URL::generate()
	 */
	public function url($url, $width = null, $height = null, $options = null) {
		return $this->url->generate($url, $width, $height, $options);
	}

}