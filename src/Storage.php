<?php

declare(strict_types=1);

namespace Bkwld\Croppa;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Interact with filesystems.
 */
final class Storage
{
    private ?FilesystemAdapter $cropsDisk = null;

    private ?FilesystemAdapter $srcDisk = null;

    private ?FilesystemAdapter $tmpDisk = null;

    private string $tmpPath = '';

    /**
     * Inject dependencies.
     */
    public function __construct(private ?array $config = null) {}

    /**
     * Factory function to create an instance and then "mount" disks.
     */
    public static function make(array $config): Storage
    {
        return (new self($config))->mount();
    }

    /**
     * Set the crops disk.
     */
    public function setCropsDisk(FilesystemAdapter $filesystemAdapter): void
    {
        $this->cropsDisk = $filesystemAdapter;
    }

    /**
     * Get the crops disk or make via the config.
     */
    public function getCropsDisk(): FilesystemAdapter
    {
        if (! $this->cropsDisk instanceof FilesystemAdapter) {
            $this->setCropsDisk($this->makeDisk($this->config['crops_disk']));
        }

        return $this->cropsDisk;
    }

    /**
     * Set the src disk.
     */
    public function setSrcDisk(FilesystemAdapter $filesystemAdapter): void
    {
        $this->srcDisk = $filesystemAdapter;
    }

    /**
     * Get the src disk or make via the config.
     */
    public function getSrcDisk(): FilesystemAdapter
    {
        if (! $this->srcDisk instanceof FilesystemAdapter) {
            $this->setSrcDisk($this->makeDisk($this->config['src_disk']));
        }

        return $this->srcDisk;
    }

    /**
     * Set the tmp disk.
     */
    public function setTmpDisk(FilesystemAdapter $filesystemAdapter): void
    {
        $this->tmpDisk = $filesystemAdapter;
    }

    /**
     * Get the tmp disk or make via the config.
     */
    public function getTmpDisk(): FilesystemAdapter
    {
        if (! $this->tmpDisk instanceof FilesystemAdapter) {
            $this->setTmpDisk($this->makeDisk($this->config['tmp_disk']));
        }

        return $this->tmpDisk;
    }

    /**
     * "Mount" disks given the config.
     */
    public function mount(): self
    {
        $this->setSrcDisk($this->makeDisk($this->config['src_disk']));
        $this->setCropsDisk($this->makeDisk($this->config['crops_disk']));

        return $this;
    }

    /**
     * Use or instantiate a Flysystem disk.
     */
    public function makeDisk(string $disk): FilesystemAdapter
    {
        return FacadesStorage::disk($disk);
    }

    /**
     * Return whether crops are stored remotely.
     */
    public function cropsAreRemote(): bool
    {
        return ! $this->getCropsDisk()->getAdapter() instanceof LocalFilesystemAdapter;
    }

    /**
     * Check if a remote crop exists.
     */
    public function cropExists(string $path): bool
    {
        return $this->getCropsDisk()->fileExists($path);
    }

    /**
     * Get the src path or throw an exception.
     */
    public function path(string $path): string
    {
        $filesystemAdapter = $this->getSrcDisk();
        if ($filesystemAdapter->fileExists($path)) {
            if ($filesystemAdapter->getAdapter() instanceof LocalFilesystemAdapter) {
                return $filesystemAdapter->path($path);
            }

            // If a tmp_disk has been configured, copy file from remote srcDisk to tmpDisk
            if ($this->config['tmp_disk']) {
                $tmpDisk = $this->getTmpDisk();
                $tmpDisk->writeStream($path, $filesystemAdapter->readStream($path));
                $this->tmpPath = $path;

                return $tmpDisk->path($path);
            }

            // With Intervention 3, this will lead to a DecoderException ("Unable to decode input")
            // We should probably throw an exception here to inform the developer that a tmp_disk is required.
            return $filesystemAdapter->url($path);
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
        } catch (FilesystemException) {
            // don't throw exception anymore as mentioned in PR #164
        }

        $this->cleanup();
    }

    /**
     * Cleanup: delete tmp file if required.
     */
    public function cleanup(): void
    {
        if ($this->tmpPath !== '') {
            $this->getTmpDisk()->delete($this->tmpPath);
            $this->tmpPath = '';
        }
    }

    /**
     * Get a local crops disks absolute path.
     */
    public function getLocalCropPath(mixed $path): string
    {
        return $this->getCropsDisk()->path($path);
    }

    /**
     * Delete src image.
     */
    public function deleteSrc(string $path): void
    {
        $this->getSrcDisk()->delete($path);
    }

    /**
     * Delete crops.
     */
    public function deleteCrops(string $path): array
    {
        $crops = $this->listCrops($path);
        $filesystemAdapter = $this->getCropsDisk();
        foreach ($crops as $crop) {
            $filesystemAdapter->delete($crop);
        }

        return $crops;
    }

    /**
     * Delete ALL crops.
     */
    public function deleteAllCrops(?string $filter = null, bool $dry_run = false): array
    {
        $crops = $this->listAllCrops($filter);
        $filesystemAdapter = $this->getCropsDisk();
        if (! $dry_run) {
            foreach ($crops as $crop) {
                $filesystemAdapter->delete($crop);
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
            fn (StorageAttributes $storageAttributes): bool => pathinfo($storageAttributes->path(), PATHINFO_BASENAME) !== $filename
                && mb_strpos(pathinfo($storageAttributes->path(), PATHINFO_FILENAME), pathinfo($filename, PATHINFO_FILENAME)) === 0
                && preg_match('#'.URL::PATTERN.'#', $storageAttributes->path()) === 1
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
            function (StorageAttributes $storageAttributes) use ($filter): bool {
                if ($filter && ! preg_match("#{$filter}#i", $storageAttributes->path())) {
                    return false;
                }

                if (! preg_match('#'.URL::PATTERN.'#', $storageAttributes->path(), $matches)) {
                    return false;
                }

                $src = $matches[1].'.'.$matches[5];

                return $this->getSrcDisk()->fileExists($src);
            }
        ));
    }

    /**
     * Take a an array of results from Flysystem's listContents and get a simpler
     * array of paths to the files, relative to the crops_disk.
     */
    private function justPaths(array $files): array
    {
        // Reset the indexes to be 0 based, mostly for unit testing
        $files = array_values($files);

        // Get just the path key
        return array_map(fn (StorageAttributes $storageAttributes): string => $storageAttributes->path(), $files);
    }
}
