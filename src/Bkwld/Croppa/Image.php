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
	 * Take the input from the URL and apply transformations on the image
	 *
	 * @param integer $width 
	 * @param integer $height
	 * @param array $options
	 * @return $this
	 */
	public function process($width, $height, $options) {
		return $this
			->autoRotate()
			->trim($options)
		;
	}

	/**
	 * Auto rotate the image based on exif data (like from phones)
	 * https://github.com/nik-kor/PHPThumb/blob/master/src/thumb_plugins/jpg_rotate.inc.php
	 */
	public function autoRotate() {
		$this->thumb->rotateJpg();
		return $this;
	}

	/**
	 * Trim the source before applying the crop.  This is designed to be used in 
	 * conjunction with a cropping UI tool like jCrop. 
	 * http://deepliquid.com/content/Jcrop.html
	 *
	 * @param array $options 
	 * @return $this
	 */
	public function trim($options) {
		if (isset($options['trim'])) $this->trimPixels($options['trim']);
		else if (isset($options['trim_perc'])) $this->trimPerc($options['trim_perc']);
		return $this;
	}

	/**
	 * Trim the source before applying the crop with as offset pixels
	 * 
	 * @param  array $options Cropping instructions as pixels
	 * @return void
	 */
	public function trimPixels($options) {
		list($x1, $y1, $x2, $y2) = $options;
		$this->thumb->crop($x1, $y1, $x2 - $x1, $y2 - $y1);
	}
	
	/**
	 * Trim the source before applying the crop with offset percentages
	 * 
	 * @param  array $options Cropping instructions as percentages
	 * @return void
	 */
	public function trimPerc($options) {
		list($x1, $y1, $x2, $y2) = $options;
		$size = (object) $this->thumb->getCurrentDimensions();
		
		// Convert percentage values to what GdThumb expects
		$x = round($x1 * $size->width);
		$y = round($y1 * $size->height);
		$width = round($x2 * $size->width - $x);
		$height = round($y2 * $size->height - $y);
		$this->thumb->crop($x, $y, $width, $height);
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