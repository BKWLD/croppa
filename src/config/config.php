<?php return array(
	
	/**
	 * The directory where source images are found.  This is generally where your 
	 * admin stores uploaded files.  Can be either an absolute path to your local 
	 * disk (the default) or the name of an IoC binding of a Flysystem  instance.
	 *
	 * @var string     Absolute path in local fileystem
	 *      | string   IoC binding name of League\Flysystem\Filesystem 
	 *      | string   IoC binding name of League\Flysystem\Cached\CachedAdapter
	 */
	'src_dir' => public_path().'/uploads',

	/**
	 * The directory where cropped images should be saved.  The route to the 
	 * cropped versions is what should be rendered in your markup; it must be a 
	 * web accessible directory.
	 *
	 * @var string     Absolute path in local fileystem
	 *      | string   IoC binding name of League\Flysystem\Filesystem 
	 *      | string   IoC binding name of League\Flysystem\Cached\CachedAdapter
	 */
	'crops_dir' => public_path().'/uploads',

	/**
	 * A regex pattern that locates the path to the image relative to the
	 * crops_dir. This path will be used to find the source image in the src_dir.
	 * The path component of the regex must exist in the first captured
	 * subpattern.  In other words, in the `preg_match` $matches[1].
	 *
	 * @var string 
	 */
	'path' => '^https?://[^/]+/uploads/(.*)$',
	
	/**
	 * Maximum number of sizes to allow for a particular source file.  This is to 
	 * limit scripts from filling up your hard drive with images.  Set to false or 
	 * comment out to have no limit.
	 *
	 * @var integer | boolean
	 */
	'max_crops' => App::isLocal() ? false : 12,

	/**
	 * The jpeg quality of generated images.  The difference between 100 and 95 
	 * usually cuts the file size in half.  Going down to 70 looks ok on photos 
	 * and will reduce filesize by more than another half but on vector files 
	 * there is noticeable aliasing.
	 *
	 * @var integer
	 */
	'jpeg_quality' => 95,

	/**
	 * Turn on interlacing to make progessive jpegs
	 *
	 * @var boolean
	 */
	'interlace' => true,

	/**
	 * Specify the host for Croppa::url() to use when generating URLs. An 
	 * altenative to the default is to use the app.url setting:
	 * 
	 *   preg_replace('#https?:#', '', Config::get('app.url'))
	 *
	 * @var string
	 */
	'host' => '//'.Request::getHttpHost(),
	
);