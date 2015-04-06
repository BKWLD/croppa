<?php namespace Bkwld\Croppa;

// Deps
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Interact with filesystems
 */
class Storage {

	/**
	 * @var Illuminate\Foundation\Application
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
	public function __construct($app, $config) {
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
	 * "Mount" disks give the config
	 *
	 * @return $this 
	 */
	public function mount() {
		$this->src_disk = $this->makeDisk($this->config['src_dir']);
		$this->crops_disk = $this->makeDisk($this->config['crops_dir']);
		return $this;
	}

	/**
	 * Return whether crops are stored remotely
	 *
	 * @return boolean 
	 */
	public function cropsAreRemote() {

		// Currently, the CachedAdapter doesn't have a getAdapter method so I can't
		// tell if the adapter is local or not.  I'm assuming that if they are using
		// the CachedAdapter, they're probably using a remote disk.  I've written
		// a PR to add getAdapter to it.
		// https://github.com/thephpleague/flysystem-cached-adapter/pull/9
		if (!method_exists($this->crops_disk, 'getAdapter')) return true;

		// Check if the crop disk is not local
		return !is_a($this->crops_disk, 'League\Flysystem\Adapter\Local');
	}

	/**
	 * Get the src image data or throw an exception
	 *
	 * @param string $path Path to image relative to dir
	 * @throws Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 * @return string
	 */
	public function getSrc($path) {
		if ($this->src_disk->has($path)) return $this->src_disk->read($path);
		else throw new NotFoundHttpException('Croppa: Referenced file missing');
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

}