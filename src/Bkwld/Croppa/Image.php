<?php namespace Bkwld\Croppa;

// Dependencies
use GdThumb;

/**
 * Wraps a croped image to provide rendering functionality
 */
class Image {

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var GdThumb
	 */
	private $thumb;

	/**
	 * Constructor
	 *
	 * @param GdThumb $thumb A thumb instance
	 * @param string $path Absolute path to the file in the filesystem
	 */
	public function __construct(GdThumb $thumb, $path) {
		$this->thumb = $thumb;
		$this->path = $path;
	}

	/**
	 * Output an image to the browser.  Accepts a string path
	 * or a PhpThumb instance
	 * 
	 * @return Binary image data
	 */
	public function show() {
		
		// If headers already sent, abort.
		if (headers_sent()) return;
		
		// Set the header for the filesize and a bunch of other stuff
		header("Content-Transfer-Encoding: binary");
		header("Accept-Ranges: bytes");
		header("Content-Length: ".filesize($this->path));
		
		// Display it
		$this->thumb->show();
	}
}