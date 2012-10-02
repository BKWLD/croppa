<?php

class Croppa {
	
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
		$pattern = '/^(.*)-([0-9_]+)x([0-9_]+)(?:-(crop|resize))?(-[0-9a-z()-]+\))?\.(jpg|jpeg|png|gif)$/i';
		if (!preg_match($pattern, $url, $matches)) return false;
		$path = $matches[1].'.'.$matches[6];
		$width = $matches[2];
		$height = $matches[3];
		$format = $matches[4];
		$options = $matches[5]; // These are not parsed, all options are grouped together raw		
		
		// See if the referenced file exists and is an image
		if (!($src = self::check_for_file($path))) return false;
		
		// Make the destination the same path
		$dst = dirname($src).'/'.basename($url);
		
		// Make sure destination is writeable
		if (!is_writable(dirname($dst))) return false;
		
		// If width and height are both wildcarded, just copy the file and be done with it
		if ($width == '_' && $height == '_') {
			copy($src, $dst);
			self::show($dst);
		}
		
		// Produce the crop
		$thumb = PhpThumbFactory::create($src);
		if ($height == '_') $thumb->resize($width, 99999);            // If no height, resize by width
		elseif ($width == '_') $thumb->resize(99999, $height);        // If no width, resize by height
		elseif ($format == 'resize') $thumb->resize($width, $height); // There is width and height, but told to resize
		else $thumb->adaptiveResize($width, $height);                 // There is width and height, so crop
		
		// Save it to disk
		$thumb->save($dst);
		
		// Display it
		self::show($thumb);
	}
	
	// ------------------------------------------------------------------
	// Private methods only to follow
	// ------------------------------------------------------------------
	
	// See if there is an existing image file that matches the request
	static private function check_for_file($path) {
		
		// Loop through all the directories files may be uploaded to
		$src_dirs = Config::get('croppa::croppa.src_dirs');
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
	
	// Output an image to the browser.  Accepts a string path
	// or a PhpThumb instance
	static private function show($src) {
		if (is_string($src)) {
			$src = PhpThumbFactory::create($src);;
		}
		$src->show();
		die;
	}
	
}