<?php namespace Bkwld\Croppa;

// Dependencies
use PhpThumbFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Croppa {
	
	/**
	 * Inject dependencies
	 * 
	 * @param array $config The config data array
	 * @return void
	 */
	private $config;
	public function __construct($config = array()) {
		$this->config = array_merge(array(
			'host' => null,
			'ignore' => null,
			'public' => null,
		), $config);
	}
	
	/**
	 * Create a URL in the Croppa syntax given different parameters.  This is a helper
	 * designed to be used from view files.	
	 * 
	 * @param string $src The path to the source
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param array $options Addtional Croppa options, passed as key/value pairs.  Like array('resize')
	 * @return string The new path to your thumbnail
	 */
	public function url($src, $width = null, $height = null, $options = null) {

		// Extract the path from a URL if a URL was provided instead of a path
		$src = parse_url($src, PHP_URL_PATH);

		// Skip croppa requests for images the ignore regexp
		if ($this->config['ignore'] && preg_match('#'.$this->config['ignore'].'#', $src)) return $this->config['host'].$src;

		// Defaults
		if (empty($src)) return; // Don't allow empty strings
		if (!$width && !$height) return $this->config['host'].$src; // Pass through if both width and height are empty
		if (!$width) $width = '_';
		else $width = round($width);
		if (!$height) $height = '_';
		else $height = round($height);
		
		// Produce the croppa syntax
		$suffix = '-'.$width.'x'.$height;
		
		// Add options.  If the key has no arguments (like resize), the key will be like [1]
		if ($options && is_array($options)) {
			foreach($options as $key => $val) {
				if (is_numeric($key)) $suffix .= '-'.$val;
				elseif (is_array($val)) $suffix .= '-'.$key.'('.implode(',',$val).')';
				else $suffix .= '-'.$key.'('.$val.')';
			}
		}
		
		// Break the path apart and put back together again
		$parts = pathinfo($src);
		$parts['dirname'] = ltrim($parts['dirname'], '/');
		$url = $this->config['host'].'/'. $parts['dirname'].'/'.$parts['filename'].$suffix;
		if (!empty($parts['extension'])) $url .= '.'.$parts['extension'];
		return $url;
	}
	
	/**
	 * Take the provided URL and, if it matches the Croppa URL schema, create
	 * the thumnail as defined in the URL schema.  If no source image can be found
	 * the function returns false.  If the URL exists, that image is outputted.  If
	 * a thumbnail can be produced, it is, and then it is outputted to the browser.
	 * 
	 * @param string $url This is actually the path, like "uploads/image.jpg"
	 * @return boolean
	 */
	public function generate($url) {
				
		// Check if the current url looks like a croppa URL.  Btw, this is a good
		// resource: http://regex101.com/.
		if (!preg_match('#'.$this->pattern().'#i', $url, $matches)) return false;
		$path = $matches[1].'.'.$matches[5];
		$width = $matches[2];
		$height = $matches[3];
		$options = $matches[4]; // These are not parsed, all options are grouped together raw

		// Increase memory limit, cause some images require a lot to resize
		ini_set('memory_limit', '128M');
		
		// Break apart options
		$options = $this->makeOptions($options);
		
		// See if the referenced file exists and is an image.  This gives us the absolute
		// to the image, given the $path which is relative to a src_dir
		if (!($src = $this->checkForFile($path))) throw new NotFoundHttpException('Croppa: Referenced file missing');
		
		// Put the croped output in the same directory as the src image
		$dst = dirname($src).'/'.basename($url);
		
		// Make sure destination is writeable
		if (!is_writable(dirname($dst))) throw new Exception('Croppa: Destination is not writeable');

		// Configure PHP Thumb
		$phpthumb_config = array();
		if (array_key_exists('quality', $options)) $phpthumb_config['jpegQuality'] = $options['quality'][0];
		else if (!empty($this->config['jpeg_quality'])) $phpthumb_config['jpegQuality'] = $this->config['jpeg_quality'];
		if (array_key_exists('interlace', $options)) $phpthumb_config['interlace'] = !empty($options['interlace'][0]);
		else if (!empty($this->config['interlace'])) $phpthumb_config['interlace'] = true;

		// Create PHP Thumb and Croppa/Image instance
		$thumb = PhpThumbFactory::create($src, $phpthumb_config);
		$image = new Image($thumb, $dst);
		
		// If width and height are both wildcarded, just copy the file and be done with it
		if ($width == '_' && $height == '_') {
			copy($src, $dst);
			return $image;
		}
		
		// Make sure that we won't exceed the the max number of crops for this image
		if ($this->tooManyCrops($src)) throw new Exception('Croppa: Max crops reached');
		
		// Auto rotate the image based on exif data (like from phones)
		// Uses: https://github.com/nik-kor/PHPThumb/blob/master/src/thumb_plugins/jpg_rotate.inc.php
		$thumb->rotateJpg();
		
		// Trim the source before applying the crop.  This is designed to be used in conjunction
		// with a cropping UI tool.
		if (array_key_exists('trim', $options) && array_key_exists('trim_perc', $options)) throw new Exception('Specify a trim OR a trip_perc option, not both');
		else if (array_key_exists('trim', $options)) $this->trim($thumb, $options['trim']);
		else if (array_key_exists('trim_perc', $options)) $this->trimPerc($thumb, $options['trim_perc']);

		// Do a quadrant adaptive resize.  Supported quadrant values are:
		// +---+---+---+
		// |   | T |   |
		// +---+---+---+
		// | L | C | R |
		// +---+---+---+
		// |   | B |   |
		// +---+---+---+
		if (array_key_exists('quadrant', $options)) {
			if ($height == '_' || $width == '_') throw new Exception('Croppa: Qudrant option needs width and height');
			if (empty($options['quadrant'][0])) throw new Exception('Croppa:: No quadrant specified');
			$quadrant = strtoupper($options['quadrant'][0]);
			if (!in_array($quadrant, array('T','L','C','R','B'))) throw new Exception('Croppa:: Invalid quadrant');
			$thumb->adaptiveResizeQuadrant($width, $height, $quadrant);
		
		// Force to 'resize'
		} elseif (array_key_exists('resize', $options)) {
			if ($height == '_' || $width == '_') throw new Exception('Croppa: Resize option needs width and height');
			$thumb->resize($width, $height);
		
		// Produce a standard crop
		} else {
			if ($height == '_') $thumb->resize($width, 99999);            // If no height, resize by width
			elseif ($width == '_') $thumb->resize(99999, $height);        // If no width, resize by height
			else $thumb->adaptiveResize($width, $height);                 // There is width and height, so crop
		}
		
		// Save it to disk
		$thumb->save($dst);

		// Return Image instance
		return $image;
	}
	
	/**
	 * Delete all crops but keep original (call after changing original)
	 * 
	 * @param $url
	 * @throws Exception
	 */
	public function reset($url) {
		foreach($this->findFilesToDelete($url, false) as $file) {
			if (!unlink($file)) throw new Exception('Croppa unlink failed: '.$file);
		}
	}

	/**
	 * Delete the source image and all the crops
	 * 
	 * @param string $url Relative path to the original source image
	 * @return null
	 */
	public function delete($url) {
		foreach($this->findFilesToDelete($url) as $file) {
			if (!unlink($file)) throw new Exception('Croppa unlink failed: '.$file);
		}
	}

	/**
	 * Make an array of the files to delete given the source image
	 * 
	 * @param string $url Relative path to the original source image. Generally preceeded with a '/'
	 * @param bool $delete_original include original image in list (needed for deleting) if true, 
	 *                              omit original if false (needed for updating with new image)
	 * @return array List of absolute paths of images
	 */
	public function findFilesToDelete($url, $delete_original = true) {
		$deleting = array();

		// Need to decode the url so that we can handle things like space characters
		$url = urldecode($url);

		// Add the source image to the list if deleting, don't add if resetting
		if (!($src = $this->checkForFile($url))) return array();
		if ($delete_original) $deleting[] = $src;

		// Loop through the contents of the source directory and delete
		// any images that contain the source directories filename and also match
		// the Croppa URL pattern
		$parts = pathinfo($src);
		$files = scandir($parts['dirname']);
		foreach($files as $file) {
			$path = $parts['dirname'].'/'.$file;
			if (strpos($file, $parts['filename']) === 0 // Quick check to check for src 
				&& !in_array($path, $deleting) // Not already added (because of $delete_original)
				&& preg_match('#'.$this->pattern().'#', $path)) {
				$deleting[] = $path;
			}
		}
		// Return the list
		return $deleting;

	}
	
	/**
	 * Return width and height values for putting in an img tag.  Uses the same arguments as Croppa::url().
	 * Used in cases where you are resizing an image along one dimension and don't know what the wildcarded
	 * image size is.  They are formatted for putting in a style() attribute.  This seems to have better support
	 * that using the old school width and height attributes for setting the initial height.
	 * 
	 * @param string $src The path to the source
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param array $options Addtional Croppa options, passed as key/value pairs.  Like array('resize')
	 * @return string i.e. "width='200px' height='200px'"
	 */
	public function sizes($src, $width = null, $height = null, $options = null) {
		
		// Get the URL to the file
		$url = $this->url($src, $width, $height, $options);
		
		// Find the local path to this file by removing the URL base and then adding the
		// path to the public directory
		$path = $this->config['public'].substr($url, strlen($this->config['host']));
		
		// Get the sizes
		if (!file_exists($path)) return null; // It may not exist if this is the first request for the img
		if (!($size = getimagesize($path))) throw new Exception('Dimensions could not be read');
		return "width:{$size[0]}px; height:{$size[1]}px;";
		
	}
	
	/**
	 * Create an image tag rather than just the URL.  Accepts the same params as Croppa::url()
	 * 
	 * @param string $src The path to the source
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param array $options Addtional Croppa options, passed as key/value pairs.  Like array('resize')
	 * @return string i.e. <img src="path/to/img.jpg" />
	 */
	public function tag($src, $width = null, $height = null, $options = null) {
		return '<img src="'.$this->url($src, $width, $height, $options).'" />';
	}
	
	/**
	 * Return the Croppa URL regex
	 *
	 * @return string
	 */
	public function pattern() {
		$pattern = '';

		// Add rest of the path up to croppa's extension
		$pattern .= '(.+)';

		// Check for the size bounds
		$pattern .= '-([0-9_]+)x([0-9_]+)';
		
		// Check for options that may have been added
		$pattern .= '(-[0-9a-zA-Z(),\-._]+)*';
		
		// Check for possible image suffixes.
		$pattern .= '\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$';

		// Return it
		return $pattern;
	}

	/**
	 * Return a pattern that enforces the src_dirs
	 *
	 * @return string
	 */
	public function directoryPattern() {
		$pattern = '^';

		// Make leading slashes optional
		$pattern .= '\/?';

		// Make sure it starts with a src dir
		$public = $this->config['public'];
		$pattern .= '(?:'.implode('|', array_map(function($dir) use ($public) {
			return preg_quote( // Escape unsafe chars
				ltrim( // Don't allow leading slashes, the generate($path) lacks them
					str_replace($public, '', $dir), 
			'/'), '#');
		}, $this->config['src_dirs'])).')';

		// Return it with the file pattern
		return $pattern.$this->pattern();
	}
	
	// ------------------------------------------------------------------
	// Generally internal methods only to follow
	// ------------------------------------------------------------------
	
	/**
	 * See if there is an existing image file that matches the request given
	 * a relative path to the image.
	 * 
	 * @param  string $path    An absolute path to an image
	 * @return string|boolean The absolute path to the file or FALSE
	 */
	public function checkForFile($path) {

		// Expect there to be a leading slash
		if (substr($path, 0, 1) != '/') $path = '/'.$path;

		// Strip src_dirs and leading slashes from the path 
		$public = $this->config['public'];
		$path = ltrim(str_replace(array_map(function($dir) use ($public) {
			return str_replace($public, '', $dir);
		}, $this->config['src_dirs']), '', $path), '/');

		// Check in path
		return $this->checkForFileByPath($path);
	}

	/**
	 * See if there is an existing image file that matches the request given
	 * a path relative to a src_dir
	 * 
	 * @param  string $url    A path to the image relative to a src_dir
	 * @return string|boolean The absolute path to the file or FALSE
	 */
	public function checkForFileByPath($path) {

		// Loop through all the directories files may be uploaded to
		$src_dirs = $this->config['src_dirs'];
		foreach($src_dirs as $dir) {
			
			// Check that directory exists
			if (!is_dir($dir)) continue;
			if (substr($dir, -1, 1) != '/') $dir .= '/';
			
			// Look for the image in the directory
			$src = $dir.$path;
			if (is_file($src) && getimagesize($src) !== false) {
				return $src;
			}
		}
		
		// None found
		return false;
	}
	
	/**
	 * Count up the number of crops that have already been created
	 * and return true if they are at the max number.
	 * For https://github.com/BKWLD/croppa/issues/1
	 * 
	 * @param  string $src Absolute path to a src image
	 * @return boolean
	 */
	public function tooManyCrops($src) {
		
		// If there is no max set, we are applying no limit
		if (empty($this->config['max_crops'])) return false;
		
		// Count up the crops
		$found = 0;
		$parts = pathinfo($src);
		$files = scandir($parts['dirname']);
		foreach($files as $file) {
			$path = $parts['dirname'].'/'.$file;
			
			// Check if this file, when stripped of Croppa suffixes, has the same name
			// as the source image.
			if (pathinfo(preg_replace('#'.$this->pattern().'#', "$1", $path), PATHINFO_FILENAME) == $parts['filename']) $found++;
			
			// We're matching against the max + 1 because the source file
			// will match but doesn't count against the crop limit
			if ($found > $this->config['max_crops'] + 1) return true;
		}
		
		// There aren't too many crops, so return false
		return false;
	}
	
	/**
	 * Create options array where each key is an option name
	 * and the value if an array of the passed arguments
	 * 
	 * @param  string $option_params Options string in the Croppa URL style
	 * @return array
	 */
	public function makeOptions($option_params) {
		$options = array();
		
		// These will look like: "-quadrant(T)-resize"
		$option_params = explode('-', $option_params);
		
		// Loop through the params and make the options key value pairs
		foreach($option_params as $option) {
			if (!preg_match('#(\w+)(?:\(([\w,.]+)\))?#i', $option, $matches)) continue;
			if (isset($matches[2])) $options[$matches[1]] = explode(',', $matches[2]);
			else $options[$matches[1]] = null;
		}

		// Return new options array
		return $options;
	}
	
	/**
	 * Trim the source before applying the crop where the input is given as
	 * offset pixels
	 * 
	 * @param  PhpThumb $thumb  
	 * @param  array $options Cropping instructions as pixels
	 * @return void
	 */
	public function trim($thumb, $options) {
		list($x1, $y1, $x2, $y2) = $options;
					
		// Apply crop to the thumb before resizing happens
		$thumb->crop($x1, $y1, $x2 - $x1, $y2 - $y1);
	}
	
	/**
	 * Trim the source before applying the crop where the input is given as
	 * offset percentages
	 * 
	 * @param  PhpThumb $thumb 
	 * @param  array $options Cropping instructions as percentages
	 * @return void
	 */
	public function trimPerc($thumb, $options) {
		list($x1, $y1, $x2, $y2) = $options;
			
		// Get the current dimensions
		$size = (object) $thumb->getCurrentDimensions();
		
		// Convert percentage values to what GdThumb expects
		$x = round($x1 * $size->width);
		$y = round($y1 * $size->height);
		$width = round($x2 * $size->width - $x);
		$height = round($y2 * $size->height - $y);
		
		// Apply crop to the thumb before resizing happens
		$thumb->crop($x, $y, $width, $height);
	}
	
}
