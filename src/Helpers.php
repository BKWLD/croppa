<?php

namespace Bkwld\Croppa;

/**
 * The public API to Croppa.  It generally passes through requests to other
 * classes.
 */
class Helpers
{
    /**
     * @var \Bkwld\Croppa\URL
     */
    private $url;

    /**
     * @var \Bkwld\Croppa\Storage
     */
    private $storage;

    /**
     * @var \Bkwld\Croppa\Handler
     */
    private $handler;

    /**
     * Dependency injection.
     */
    public function __construct(URL $url, Storage $storage, Handler $handler)
    {
        $this->url = $url;
        $this->storage = $storage;
        $this->handler = $handler;
    }

    /**
     * Delete source image and all of it's crops.
     *
     * @see Bkwld\Croppa\Storage::deleteSrc()
     * @see Bkwld\Croppa\Storage::deleteCrops()
     */
    public function delete(string $url)
    {
        $path = $this->url->relativePath($url);
        $this->storage->deleteSrc($path);
        $this->storage->deleteCrops($path);
    }

    /**
     * Delete just the crops, leave the source image.
     *
     * @see Bkwld\Croppa\Storage::deleteCrops()
     */
    public function reset(string $url)
    {
        $path = $this->url->relativePath($url);
        $this->storage->deleteCrops($path);
    }

    /**
     * Create an image tag rather than just the URL.  Accepts the same params as url().
     *
     * @see Bkwld\Croppa\URL::generate()
     */
    public function tag(string $url, ?int $width = null, ?int $height = null, ?array $options = null): string
    {
        return '<img src="'.$this->url->generate($url, $width, $height, $options).'">';
    }

    /**
     * Pass through URL requests to URL->generate().
     *
     * @see Bkwld\Croppa\URL::generate()
     */
    public function url(string $url, ?int $width = null, ?int $height = null, ?array $options = null): string
    {
        return $this->url->generate($url, $width, $height, $options);
    }

    /**
     * Render image.
     *
     * @see Bkwld\Croppa\URL::generate()
     */
    public function render(string $url): string
    {
        return $this->handler->render($url);
    }
}
