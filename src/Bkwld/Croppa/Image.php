<?php namespace Bkwld\Croppa;

// Dependencies
use PhpThumbFactory;

/**
 * Wraps PhpThumb with the API used by Croppa to transform the src image
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
	 * @param array $options PHPThumb options
	 */
	public function __construct($data, $options = []) {
		$this->thumb = PhpThumbFactory::create($data, $options, true);
	}

	/**
	 * Take the input from the URL and apply transformations on the image
	 *
	 * @param integer $width
	 * @param integer $height
	 * @param array $options
	 * @return $this
	 */
	public function process($width = null, $height = null, $options = []) {
		return $this
			->autoRotate()
			->trim($options)
			->resizeAndOrCrop($width, $height, $options)
			->applyFilters($options)
		;
	}

	/**
	 * Apply filters that have been defined in the config as seperate classes.
	 *
	 * @param array $filters Array of filter instances
	 * @return $this
	 */
	public function applyFilters($options) {
		if (isset($options['filters']) && is_array($options['filters'])) {
			array_map(function($filter) {
				$this->thumb = $filter->applyFilter($this->thumb);
			}, $options['filters']);
		}
		return $this;
	}

	/**
	 * Auto rotate the image based on exif data (like from phones)
	 * https://github.com/nik-kor/PHPThumb/blob/master/src/thumb_plugins/jpg_rotate.inc.php
	 *
	 * @return $this
	 */
	public function autoRotate() {
		$this->thumb->rotateJpg();
		return $this;
	}

	/**
	 * Determine which trim to apply.
	 *
	 * @param array $options
	 * @return $this
	 */
	public function trim($options) {
		if (isset($options['trim'])) return $this->trimPixels($options['trim']);
		if (isset($options['trim_perc'])) return $this->trimPerc($options['trim_perc']);
		return $this;
	}

	/**
	 * Trim the source before applying the crop with as offset pixels
	 *
	 * @param  array $coords Cropping instructions as pixels
	 * @return $this
	 */
	public function trimPixels($coords) {
		list($x1, $y1, $x2, $y2) = $coords;
		$this->thumb->crop($x1, $y1, $x2 - $x1, $y2 - $y1);
		return $this;
	}

	/**
	 * Trim the source before applying the crop with offset percentages
	 *
	 * @param  array $coords Cropping instructions as percentages
	 * @return $this
	 */
	public function trimPerc($coords) {
		list($x1, $y1, $x2, $y2) = $coords;
		$size = (object) $this->thumb->getCurrentDimensions();

		// Convert percentage values to what GdThumb expects
		$x = round($x1 * $size->width);
		$y = round($y1 * $size->height);
		$width = round($x2 * $size->width - $x);
		$height = round($y2 * $size->height - $y);
		$this->thumb->crop($x, $y, $width, $height);
		return $this;
	}

	/**
	 * Determine which resize and crop to apply
	 *
	 * @param integer $width
	 * @param integer $height
	 * @param array $options
	 * @return $this
	 */
	public function resizeAndOrCrop($width, $height, $options) {
		if (!$width && !$height) return $this;
		if (isset($options['quadrant'])) return $this->cropQuadrant($width, $height, $options);
		if (array_key_exists('resize', $options) || !$width || !$height) return $this->resize($width, $height);
		return $this->crop($width, $height);
	}

	/**
	 * Do a quadrant adaptive resize.  Supported quadrant values are:
	 * +---+---+---+
	 * |   | T |   |
	 * +---+---+---+
	 * | L | C | R |
	 * +---+---+---+
	 * |   | B |   |
	 * +---+---+---+
	 *
	 * @param integer $width
	 * @param integer $height
	 * @param array $options
	 * @throws Exception
	 * @return $this
	 */
	public function cropQuadrant($width, $height, $options) {
		if (!$height|| !$width) throw new Exception('Croppa: Qudrant option needs width and height');
		if (empty($options['quadrant'][0])) throw new Exception('Croppa:: No quadrant specified');
		$quadrant = strtoupper($options['quadrant'][0]);
		if (!in_array($quadrant, array('T','L','C','R','B'))) throw new Exception('Croppa:: Invalid quadrant');
		$this->thumb->adaptiveResizeQuadrant($width, $height, $quadrant);
		return $this;
	}

	/**
	 * Resize with no cropping
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return $this
	 */
	public function resize($width, $height) {
		if ($width && $height) $this->thumb->resize($width, $height);
		else if (!$width) $this->thumb->resize(99999, $height);
		else if (!$height) $this->thumb->resize($width, 99999);
		return $this;
	}

	/**
	 * Resize and crop
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return $this
	 */
	public function crop($width, $height) {

		// GdThumb will not enforce the requested aspect ratio if the image is too
		// small, so we manually calculate what the size should be if the aspect
		// ratio is preserved.
		$options = $this->thumb->getOptions();
		if (empty($options['resizeUp'])) {
			$size = $this->thumb->getCurrentDimensions();
			$ratio = $width / $height;
			if ($size['width'] < $width) {
				$width = $size['width'];
				$height = $size['width'] / $ratio;
			}
			if ($size['height'] < $height) {
				$height = $size['height'];
				$width = $size['height'] * $ratio;
			}
		}

		// Do a normal adpative resize
		$this->thumb->adaptiveResize($width, $height);
		return $this;
	}

	/**
	 * Get the image data
	 *
	 * @return string Image data
	 */
	public function get() {
		return $this->thumb->getImageAsString();
	}
}
