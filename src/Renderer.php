<?php

namespace Bkwld\Croppa;

class Renderer
{
    protected URL $url;
    protected Storage $storage;
    protected ?array $config;

    /**
     * @param URL $url
     * @param Storage $storage
     * @param array|null $config
     */
    public function __construct(
        URL $url,
        Storage $storage,
        ?array $config = null
    )
    {
        $this->url = $url;
        $this->storage = $storage;
        $this->config = $config;
    }

    /**
     * Render image. Return the path to the crop relative to the storage disk.
     * @param string $requestPath
     * @return string|null
     * @throws Exception
     */
    public function render(string $requestPath): ?string
    {
        $params = ParameterBucket::createFrom($requestPath);
        $urlOptions = $params?->getUrlOptions() ?? [];
        $configOptions = $this->url->config($urlOptions);

        $cropPath = $this->getRelativeCropPath(
            $requestPath,
            $configOptions
        );

        if ($this->shouldReturnExistingCrop($cropPath)) {
            return $cropPath;
        }

        if (!$params) {
            return null;
        }

        $this->checkCropLimit($params->getPath());
        $this->increaseMemoryLimitIfNeeded();
        $image = $this->buildImage($params);
        $this->processAndWriteImage($image, $cropPath, $params);

        return $cropPath;
    }

    /**
     * Get crop path relative to its directory.
     * @throws Exception
     */
    public function getRelativeCropPath(
        string $requestPath,
        array $options
    ): string
    {
        $relativePath = $this->url->relativePath($requestPath);

        $format = data_get($options, 'format');

        if ($format) {
            $relativePath = $this->replaceOriginalFileSuffix(
                $relativePath,
                $format
            );
        }

        return $relativePath;
    }

    public function replaceOriginalFileSuffix(
        string $path,
        string $suffix
    ): string
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);
        $fileName = pathinfo($path, PATHINFO_FILENAME);

        return sprintf(
            '%s/%s.%s',
            $dirname,
            $fileName,
            $suffix
        );
    }

    /**
     * TODO: Shouldn't already existing crops be returned no matter if they are stored locally or remotely.
     *  Is this intentional?
     *  Think about it and change it if needed. @see self::render()
     * Determine if the existing crop should be returned.
     * @param string $cropPath
     * @return bool
     */
    public function shouldReturnExistingCrop(string $cropPath): bool
    {
        return $this->storage->cropsAreRemote() &&
            $this->storage->cropExists($cropPath);
    }

    /**
     * Check if there are too many crops already.
     * @param string $path
     * @throws Exception
     */
    public function checkCropLimit(string $path): void
    {
        if ($this->storage->tooManyCrops($path)) {
            throw new Exception('Croppa: Max crops');
        }
    }

    /**
     * Increase memory limit if needed.
     */
    public function increaseMemoryLimitIfNeeded(): void
    {
        if ($this->config['memory_limit'] !== null) {
            ini_set('memory_limit', $this->config['memory_limit']);
        }
    }

    /**
     * Build a new image using fetched image data.
     */
    protected function buildImage(ParameterBucket $params): Image
    {
        return new Image(
            $this->storage->path($params->getPath()),
            $params->config()
        );
    }

    /**
     * Process the image and write its data to disk.
     * @throws Exception
     */
    protected function processAndWriteImage(
        Image $image,
        string $cropPath,
        ParameterBucket $params
    ): void
    {
        $newImage = $image->process(
            $params->getWidth(),
            $params->getHeight(),
            $params->getUrlOptions()
        );

        $this->storage->writeCrop(
            $cropPath,
            $newImage->get()
        );
    }
}