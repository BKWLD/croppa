<?php

class Croppa {
	
	/**
	 * Persist the config
	 * @param array $data The config data array
	 * @return void
	 */
	static private $config;
	static public function config($data) {
		self::$config = $data;
	}
	
	/**
	 * Create a URL in the Croppa syntax given different parameters.  This is a helper
	 * designed to be used from view files.	
	 * @param string $src The path to the source
	 * @param integer $width Target width
	 * @param integer $height Target height
	 * @param string $format One of the supported Croppa formats: 'crop' or 'resize'
	 * @param array $options Addtional Croppa options, passed as key/value pairs
	 * @return string The new path to your thumbnail
	 */
	static public function url($src, $width = null, $height = null, $format = null, $options = null) {
		
		// Defaults
		if (empty($src)) return; // Don't allow empty strings
		if (empty($width)) $width = '_';
		if (empty($height)) $height = '_';
		
		// Produce the croppa syntax
		$suffix = '-'.$width.'x'.$height;
		if ($format) $suffix .= '-'.$format;
		if ($options && is_array($options)) {
			foreach($options as $key => $val) {
				$suffix .= '-'.$key.'('.$val.')';
			}
		}
		
		// Break the path apart and put back together again
		$parts = pathinfo($src);
		return $parts['dirname'].'/'.$parts['filename'].$suffix.'.'.$parts['extension'];
	}
	
	/**
	 * Take the provided URL and, if it matches the Croppa URL schema, create
	 * the thumnail as defined in the URL schema.  If no source image can be found
	 * the function returns false.  If the URL exists, that image is outputted.  If
	 * a thumbnail can be produced, it is, and then it is outputted to the browser.
	 * @param string $url
	 * @return boolean
	 */
	static public function handle_404($url) {
		
		// Make sure this file doesn't exist.  There's no reason it should if the 404
		// capturing is working right, but just in case
		if ($src = self::check_for_file($url)) {
			self::show($src);
		}
				
		// Check if the current url looks like a croppa URL.  Btw, this is a good
		// resource: http://regexpal.com/.
		$pattern = '#^(.*)-([0-9_]+)x([0-9_]+)(?:-(crop|resize))?(-[0-9a-z(),\-]+)*\.(jpg|jpeg|png|gif)$#i';
		if (!preg_match($pattern, $url, $matches)) return false;
		$path = $matches[1].'.'.$matches[6];
		$width = $matches[2];
		$height = $matches[3];
		$format = $matches[4];
		$options = $matches[5]; // These are not parsed, all options are grouped together raw

		// Break apart options
		$options = self::make_options($options);
		
		// See if the referenced file exists and is an image
		if (!($src = self::check_for_file($path))) throw new Croppa\Exception('Croppa: Referenced file missing');
		
		// Make the destination the same path
		$dst = dirname($src).'/'.basename($url);
		
		// Make sure destination is writeable
		if (!is_writable(dirname($dst))) throw new Croppa\Exception('Croppa: Destination is not writeable');
		
		// If width and height are both wildcarded, just copy the file and be done with it
		if ($width == '_' && $height == '_') {
			copy($src, $dst);
			self::show($dst);
		}
		
		// Make sure that we won't exceed the the max number of crops for this image
		if (self::tooManyCrops($src)) throw new Croppa\Exception('Croppa: Max crops reached');

		// Create the PHPThumb instance
		$thumb = PhpThumbFactory::create($src);
		
		// Auto rotate the image based on exif data (like from phones)
		// Uses: https://github.com/nik-kor/PHPThumb/blob/master/src/thumb_plugins/jpg_rotate.inc.php
		$thumb->rotateJpg();

		// Do a quadrant resize.  Supported quadrant values are:
		// +---+---+---+
		// |   | T |   |
		// +---+---+---+
		// | L | C | R |
		// +---+---+---+
		// |   | B |   |
		// +---+---+---+
		if (array_key_exists('quadrant', $options)) {
			if ($height == '_' || $width == '_') throw new Croppa\Exception('Croppa: You must crop to use the quadrant option');
			if (empty($options['quadrant'][0])) throw new Croppa\Exception('Croppa:: No quadrant specified');
			$quadrant = strtoupper($options['quadrant'][0]);
			if (!in_array($quadrant, array('T','L','C','R','B'))) throw new Croppa\Exception('Croppa:: Invalid quadrant');
			$thumb->adaptiveResizeQuadrant($width, $height, $quadrant);
		
		// Produce a standard crop
		} else {
			if ($height == '_') $thumb->resize($width, 99999);            // If no height, resize by width
			elseif ($width == '_') $thumb->resize(99999, $height);        // If no width, resize by height
			elseif ($format == 'resize') $thumb->resize($width, $height); // There is width and height, but told to resize
			else $thumb->adaptiveResize($width, $height);                 // There is width and height, so crop
		}
		
		// Save it to disk
		$thumb->save($dst);
		
		// Display it
		self::show($thumb, $dst);
	}
	
	/**
	 * Delete the source image and all the crops
	 * @param string $src Relative path to the original source image
	 * @return type
	 */
	static public function delete($url) {
	
		// Delete the source image		
		if (!($src = self::check_for_file($url))) {
			return false;
		}
		unlink($src);
		
		// Loop through the contents of the source directory and delete
		// any images that contain the source directories filename
		$parts = pathinfo($src);
		$files = scandir($parts['dirname']);
		foreach($files as $file) {
			if (strpos($file, $parts['filename']) !== false) {
				if (!unlink($parts['dirname'].'/'.$file)) throw new Croppa\Exception('Croppa: Unlink failed');
			}
		}
		
	}
	
	// ------------------------------------------------------------------
	// Private methods only to follow
	// ------------------------------------------------------------------
	
	// See if there is an existing image file that matches the request
	static private function check_for_file($path) {
		
		// Loop through all the directories files may be uploaded to
		$src_dirs = self::$config['src_dirs'];
		foreach($src_dirs as $dir) {
			
			// Check that directory exists
			if (!is_dir($dir)) continue;
			if (substr($dir, -1, 1) != '/') $dir .= '/';
			
			// Look for the image in the directory
			$src = $dir.$path;
			if (file_exists($src) && getimagesize($src) !== false) {
				return $src;
			}
		}
		
		// None found
		return false;
	}
	
	// See count up the number of crops that have already been created
	// and return true if they are at the max number.
	// For: https://github.com/BKWLD/croppa/issues/1
	static private function tooManyCrops($src) {
		
		// If there is no max set, we are applying no limit
		if (empty(self::$config['max_crops'])) return false;
		
		// Count up the crops
		$found = 0;
		$parts = pathinfo($src);
		$files = scandir($parts['dirname']);
		foreach($files as $file) {
			if (strpos($file, $parts['filename']) !== false) $found++;
			
			// We're matching against the max + 1 because the source file
			// will match but doesn't count against the crop limit
			if ($found > self::$config['max_crops']) return true;
		}
		
		// There aren't too many crops, so return false
		return false;
	}
	
	// Output an image to the browser.  Accepts a string path
	// or a PhpThumb instance
	static private function show($src, $path = null) {
		
		// Handle string paths
		if (is_string($src)) {
			$path = $src;
			$src = PhpThumbFactory::create($src);
		
		// Handle PhpThumb instances
		} else if (empty($path)) {
			throw new Exception('$path is required by Croppa');
		}
		
		// Set the header for the filesize and a bunch of other stuff
		header("Content-Transfer-Encoding: binary");
		header("Accept-Ranges: bytes");
    header("Content-Length: ".filesize($path));
		
		// Display it
		$src->show();
		die;
	}
	
	// Create options array where each key is an option name
	// and the value if an array of the passed arguments
	static private function make_options($option_params) {
		$options = array();
		
		// These will look like: "-quadrant(T)-resize(30)"
		$option_params = explode('-', $option_params);
		
		// Loop through the params and make the options key value pairs
		foreach($option_params as $option) {
			if (!preg_match('#(\w+)(?:\(([\w,]+)\))?#i', $option, $matches)) continue;
			if (isset($matches[2])) $options[$matches[1]] = explode(',', $matches[2]);
			else $options[$matches[1]] = null;
		}

		// Return new options array
		return $options;
	}
	
}