<?php namespace Bkwld\Croppa;

// Deps
use League\Flysystem\Adapter\Local as Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FileExistsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Interact with filesystems
 */
class Storage {

	/**
	 * @var Illuminate\Container\Container
	 */
	private $app;

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var League\Flysystem\Filesystem | League\Flysystem\Cached\CachedAdapter
	 */
	private $crops_disk;

	/**
	 * @var League\Flysystem\Filesystem | League\Flysystem\Cached\CachedAdapter
	 */
	private $src_disk;

	/**
	 * Inject dependencies
	 *
	 * @param Illuminate\Container\Container
	 * @param array $config 
	 */
	public function __construct($app = null, $config = null) {
		$this->app = $app;
		$this->config = $config;
	}

	/**
	 * Factory function to create an instance and then "mount" disks
	 *
	 * @param Illuminate\Container\Container
	 * @param array $config 
	 * @return Bkwld\Croppa\Storage
	 */
	static public function make($app, $config) {
		return with(new static($app, $config))->mount();
	}

	/**
	 * Set the crops disk
	 *
	 * @param League\Flysystem\Filesystem | League\Flysystem\Cached\CachedAdapter
	 */
	public function setCropsDisk($disk) {
		$this->crops_disk = $disk;
	}

	/**
	 * Set the src disk
	 *
	 * @param League\Flysystem\Filesystem | League\Flysystem\Cached\CachedAdapter
	 */
	public function setSrcDisk($disk) {
		$this->src_disk = $disk;
	}

	/**
	 * "Mount" disks given the config
	 *
	 * @return $this 
	 */
	public function mount() {
		$this->setSrcDisk($this->makeDisk($this->config['src_dir']));
		$this->setCropsDisk($this->makeDisk($this->config['crops_dir']));
		return $this;
	}

	/**
	 * Use or instantiate a Flysystem disk
	 *
	 * @param string $dir The value from one of the config dirs
	 * @return League\Flysystem\Filesystem | League\Flysystem\Cached\CachedAdapter
	 */
	public function makeDisk($dir) {

		// Check if the dir refers to an IoC binding and return it
		if ($this->app->bound($dir) 
			&& ($instance = $this->app->make($dir))
			&& (is_a($instance, 'League\Flysystem\Filesystem') 
				|| is_a($instance, 'League\Flysystem\Cached\CachedAdapter'))
			) return $instance;

		// Instantiate a new Flysystem instance for local dirs
		return new Filesystem(new Adapter($dir));
	}

	/**
	 * Return whether crops are stored remotely
	 *
	 * @return boolean 
	 */
	public function cropsAreRemote() {
		$adapter = $this->crops_disk->getAdapter();

		// Currently, the CachedAdapter doesn't have a getAdapter method so I can't
		// tell if the adapter is local or not.  I'm assuming that if they are using
		// the CachedAdapter, they're probably using a remote disk.  I've written
		// a PR to add getAdapter to it.
		// https://github.com/thephpleague/flysystem-cached-adapter/pull/9
		if (is_a($adapter, 'League\Flysystem\Cached\CachedAdapter')) {
			if (method_exists($adapter, 'getAdapter')) {
				$adapter = $adapter->getAdapter(); // Get the ACTUAL adapter
			} else return true;
		}

		// Check if the crop disk is not local
		return !is_a($adapter, 'League\Flysystem\Adapter\Local');
	}

	/**
	 * Check if a remote crop exists
	 *
	 * @param string $path 
	 * @return boolean 
	 */
	public function cropExists($path) {
		return $this->crops_disk->has($path);
	}

	/**
	 * Get the src image data or throw an exception
	 *
	 * @param string $path Path to image relative to dir
	 * @throws Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @return string
	 */
	public function readSrc($path) {
		if ($this->src_disk->has($path)) return $this->src_disk->read($path);
		else throw new NotFoundHttpException('Croppa: Src image is missing');
	}

	/**
	 * Write the cropped image contents to disk
	 *
	 * @param string $path Where to save the crop
	 * @param string $contents The image data
	 * @throws Exception 
	 * @return void
	 */
	public function writeCrop($path, $contents) {
		try {
			$this->crops_disk->write($path, $contents);
		} catch(FileExistsException $e) {
			throw new Exception("Croppa: Crop already exists at $path. You probably
				have a misconfiguration. Make sure that the URL to your crop can be
				transformed by the `path` config to your `crop_dir`.");
		}
		
	}

	/**
	 * Get a local crops disks absolute path
	 *
	 * @return string 
	 */
	public function getLocalCropsDirPath() {
		return $this->crops_disk->getAdapter()->getPathPrefix();
	}

	/**
	 * Delete src image
	 *
	 * @param string $path Path to src image
	 */
	public function deleteSrc($path) {
		$this->src_disk->delete($path);
	}

	/**
	 * Delete crops
	 *
	 * @param string $path Path to src image
	 */
	public function deleteCrops($path) {
		foreach($this->listCrops($path) as $crop) {
			$this->crops_disk->delete($crop);
		}
	}

	/**
	 * Count up the number of crops that have already been created
	 * and return true if they are at the max number.
	 * 
	 * @param  string $path Path to the src image
	 * @return boolean
	 */
	public function tooManyCrops($path) {
		if (empty($this->config['max_crops'])) return false;
		return count($this->listCrops($path)) >= $this->config['max_crops'];
	}

	/**
	 * Find all the crops that have been generated for a src path
	 *
	 * @param string $path 
	 * @return array 
	 */
	public function listCrops($path) {
		$src = basename($path);

		// Map the filtered list to get just the paths
		return array_map(function($file) {
			return $file['path'];

		// Filter the list of files in the dir to find crops.  Using array_values
		// to reset the indexes to be 0 based, mostly for unit testing.
		}, array_values(array_filter($this->crops_disk->listContents(dirname($path)), 
			function($file) use ($src) {

			// Don't return the source image, we're JUST getting crops
			return $file['basename'] != $src

			// Test that the crop begins with the src's path, that the crop is FOR
			// the src
			&& strpos($file['basename'], pathinfo($src, PATHINFO_FILENAME)) === 0

			// Make sure that the crop matches that Croppa file regex
			&& preg_match('#'.URL::PATTERN.'#', $file['path']);
		})));

	}
}
