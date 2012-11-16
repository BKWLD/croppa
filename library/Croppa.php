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
		
		if ( !empty( self::$config['custom_savepath_thumbnails']) ) {
			$pathinfo = pathinfo($url);
			$dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $pathinfo['dirname'] . '/' . self::$config['custom_savepath_thumbnails'] . '/';

			if (!is_dir($dir)) mkdir( $dir );
			if (!is_writable($dir)) chmod($dir, 0777);

			if ($src = self::check_for_file( $dir . $pathinfo['filename'] . $pathinfo['extension'] )) 
				self::show($src);
		}	

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

		//Check the config and override the agove argument
		if (!empty(self::$config['custom_savepath_thumbnails'])) 
			$dst = dirname($src).'/' . self::$config['custom_savepath_thumbnails'] .'/'.basename($url);

		// Make sure destination is writeable
		if (!is_writable(dirname($dst))) return false;
		
		// If width and height are both wildcarded, just copy the file and be done with it
		if ($width == '_' && $height == '_') {
			copy($src, $dst);
			self::show($dst);
		}
		
		// Make sure that we won't exceed the the max number of crops for this image
		if (self::tooManyCrops($src)) return false;

		// Produce the crop
		$thumb = PhpThumbFactory::create($src);
		if ($height == '_') $thumb->resize($width, 99999);            // If no height, resize by width
		elseif ($width == '_') $thumb->resize(99999, $height);        // If no width, resize by height
		elseif ($format == 'resize') $thumb->resize($width, $height); // There is width and height, but told to resize
		else $thumb->adaptiveResize($width, $height);                 // There is width and height, so crop
		
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
				unlink($parts['dirname'].'/'.$file);
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
	
}