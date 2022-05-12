<?php

namespace Bkwld\Croppa;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Interact with filesystems.
 */
class Storage
{
    /**
     * @var Illuminate\Container\Container
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $path;

    /**
     * @var FilesystemAdapter
     */
    private $cropsDisk;

    /**
     * @var FilesystemAdapter
     */
    private $srcDisk;

    /**
     * Inject dependencies.
     *
     * @param Illuminate\Container\Container
     * @param null|mixed $app
     */
    public function __construct($app = null, ?array $config = null)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Factory function to create an instance and then "mount" disks.
     *
     * @param Illuminate\Container\Container
     * @param mixed $app
     *
     * @return Bkwld\Croppa\Storage
     */
    public static function make($app, array $config)
    {
        return with(new static($app, $config))->mount();
    }

    /**
     * Set the crops disk.
     */
    public function setCropsDisk(FilesystemAdapter $disk): void
    {
        $this->cropsDisk = $disk;
    }

    /**
     * Get the crops disk or make via the config.
     */
    public function getCropsDisk(): FilesystemAdapter
    {
        if (empty($this->cropsDisk)) {
            $this->setCropsDisk($this->makeDisk($this->config['crops_dir']));
        }

        return $this->cropsDisk;
    }

    /**
     * Set the src disk.
     */
    public function setSrcDisk(FilesystemAdapter $disk): void
    {
        $this->srcDisk = $disk;
    }

    /**
     * Get the src disk or make via the config.
     */
    public function getSrcDisk(): FilesystemAdapter
    {
        if (empty($this->srcDisk)) {
            $this->setSrcDisk($this->makeDisk($this->config['src_dir']));
        }

        return $this->srcDisk;
    }

    /**
     * "Mount" disks given the config.
     */
    public function mount(): self
    {
        $this->setSrcDisk($this->makeDisk($this->config['src_dir']));
        $this->setCropsDisk($this->makeDisk($this->config['crops_dir']));

        return $this;
    }

    /**
     * Use or instantiate a Flysystem disk.
     */
    public function makeDisk(string $disk): FilesystemAdapter
    {
        $this->path = FacadesStorage::disk($disk)->path('/');

        return FacadesStorage::disk($disk);
    }

    /**
     * Return whether crops are stored remotely.
     */
    public function cropsAreRemote(): bool
    {
        return $this->config['crops_are_remote'];
    }

    /**
     * Check if a remote crop exists.
     */
    public function cropExists(string $path): bool
    {
        return $this->getCropsDisk()->fileExists($path);
    }

    /**
     * Get the src image data or throw an exception.
     *
     * @throws Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function readSrc(string $path): string
    {
        $disk = $this->getSrcDisk();
        if ($disk->fileExists($path)) {
            return $disk->read($path);
        }

        throw new NotFoundHttpException('Croppa: Src image is missing');
    }

    /**
     * Write the cropped image contents to disk.
     *
     * @throws Exception
     */
    public function writeCrop(string $path, string $contents): void
    {
        try {
            $this->getCropsDisk()->write($path, $contents);
        } catch (FilesystemException $e) {
            // don't throw exception anymore as mentioned in PR #164
        }
    }

    /**
     * Get a local crops disks absolute path.
     */
    public function getLocalCropsDirPath(): string
    {
        return $this->path;
    }

    /**
     * Delete src image.
     */
    public function deleteSrc(string $path)
    {
        $this->getSrcDisk()->delete($path);
    }

    /**
     * Delete crops.
     */
    public function deleteCrops(string $path): array
    {
        $crops = $this->listCrops($path);
        $disk = $this->getCropsDisk();
        foreach ($crops as $crop) {
            $disk->delete($crop);
        }

        return $crops;
    }

    /**
     * Delete ALL crops.
     */
    public function deleteAllCrops(?string $filter = null, bool $dry_run = false): array
    {
        $crops = $this->listAllCrops($filter);
        $disk = $this->getCropsDisk();
        if (!$dry_run) {
            foreach ($crops as $crop) {
                $disk->delete($crop);
            }
        }

        return $crops;
    }

    /**
     * Count up the number of crops that have already been created
     * and return true if they are at the max number.
     */
    public function tooManyCrops(string $path): bool
    {
        if (empty($this->config['max_crops'])) {
            return false;
        }

        return count($this->listCrops($path)) >= $this->config['max_crops'];
    }

    /**
     * Find all the crops that have been generated for a src path.
     */
    public function listCrops(string $path): array
    {
        // Get the filename and dir
        $filename = basename($path);
        $dir = dirname($path);
        if ($dir === '.') {
            $dir = '';
        } // Flysystem doesn't like "." for the dir

        // Filter the files in the dir to just crops of the image path
        return $this->justPaths(array_filter(
            $this->getCropsDisk()->listContents($dir)->toArray(),
            function ($file) use ($filename) {
                // Don't return the source image, we're JUST getting crops
                return pathinfo($file['path'], PATHINFO_BASENAME) !== $filename
            // Test that the crop begins with the src's path, that the crop is FOR
            // the src
            && mb_strpos(pathinfo($file['path'], PATHINFO_FILENAME), pathinfo($filename, PATHINFO_FILENAME)) === 0

            // Make sure that the crop matches that Croppa file regex
            && preg_match('#'.URL::PATTERN.'#', $file['path']);
            }
        ));
    }

    /**
     * Find all the crops witin the crops dir, optionally applying a filtering
     * regex to them.
     */
    public function listAllCrops(?string $filter = null): array
    {
        return $this->justPaths(array_filter(
            $this->getCropsDisk()->listContents('', true)->toArray(),
            function ($file) use ($filter) {
                // If there was a filter, force it to match
                if ($filter && !preg_match("#{$filter}#i", $file['path'])) {
                    return;
                }

                // Check that the file matches the pattern and get at the parts to make to
                // make the path to the src
                if (!preg_match('#'.URL::PATTERN.'#', $file['path'], $matches)) {
                    return false;
                }
                $src = $matches[1].'.'.$matches[5];

                // Test that the src file exists
                return $this->getSrcDisk()->fileExists($src);
            }
        ));
    }

    /**
     * Take a an array of results from Flysystem's listContents and get a simpler
     * array of paths to the files, relative to the crops_dir.
     */
    protected function justPaths(array $files): array
    {
        // Reset the indexes to be 0 based, mostly for unit testing
        $files = array_values($files);

        // Get just the path key
        return array_map(function ($file) {
            return $file['path'];
        }, $files);
    }
}
