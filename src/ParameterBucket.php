<?php

namespace Bkwld\Croppa;

class ParameterBucket
{
    protected string $path;
    protected ?int $width;
    protected ?int $height;
    protected array $urlOptions;
    protected URL|null $urlHelper;

    public function __construct(
        string $path,
        ?int $width,
        ?int $height,
        array $urlOptions
    )
    {
        $this->path = $path;
        $this->width = $width;
        $this->height = $height;
        $this->urlOptions = $urlOptions;
    }

    /**
     * @param string $requestPath
     * @return static|null
     * @throws Exception
     */
    public static function createFrom(string $requestPath): ?static
    {
        $url = app(URL::class);
        $params = $url->parse($requestPath);

        if (!$params) {
            return null;
        }

        [$path, $width, $height, $options] = $params;

        return new self(
            $path,
            $width,
            $height,
            $options
        );
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @return array
     */
    public function getUrlOptions(): array
    {
        return $this->urlOptions;
    }

    /**
     * Take options in the URL and options from the config file
     * and produce a config array.
     */
    public function config(): array
    {
        return $this->getUrlHelper()->config($this->getUrlOptions());
    }

    protected function getUrlHelper() {
        $this->urlHelper = $this->urlHelper ?? app(URL::class);
        return $this->urlHelper;
    }
}
