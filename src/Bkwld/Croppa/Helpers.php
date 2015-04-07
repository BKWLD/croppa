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
	 * @see Bkwld\Croppa\Storage::deleteSrc()
	 */
	public function delete() {
		return call_user_func_array([$this->storage, 'deleteSrc'], func_get_args());
	}

	/**
	 * Delete just the crops, leave the source image
	 * 
	 * @see Bkwld\Croppa\Storage::deleteCrops()
	 */
	public function reset() {
		return call_user_func_array([$this->storage, 'deleteCrops'], func_get_args());
	}

	/**
	 * Create an image tag rather than just the URL.  Accepts the same params as url()
	 *
	 * @see Bkwld\Croppa\URL::generate()
	 */
	public function tag() {
		return '<img src="'.call_user_func_array([$this->url, 'url'], func_get_args()).'">';
	}

	/**
	 * Pass through URL requrests to URL->generate().
	 *
	 * @see Bkwld\Croppa\URL::generate()
	 */
	public function url() {
		return call_user_func_array([$this->url, 'generate'], func_get_args());
	}

}