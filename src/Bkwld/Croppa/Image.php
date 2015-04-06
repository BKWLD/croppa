<?php namespace Bkwld\Croppa;

// Dependencies
use PhpThumbFactory;

/**
 * Wraps a croped image to provide rendering functionality
 */
class Image {

	/**
	 * @var GdThumb
	 */
	private $thumb;

	/**
	 * Constructor
	 *
	 * @param string $data Image data as a string
	 * @param array $options 
	 */
	public function __construct($data, $config) {
		$this->thumb = PhpThumbFactory::create($data, $config, true);
	}

	/**
	 * Output to the browser.
	 * 
	 * @return Binary image data
	 */
	public function show() {
		
		// If headers already sent, abort
		if (headers_sent()) return;
		
		// Set the header for the filesize and a bunch of other stuff
		header("Content-Transfer-Encoding: binary");
		header("Accept-Ranges: bytes");
		// header("Content-Length: ".filesize($this->path));
		
		// Display it
		$this->thumb->show();
	}
}