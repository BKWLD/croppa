<?php

namespace Bkwld\Croppa;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Wraps Intervention Image with the API used by Croppa to transform the src image.
 */
class Image
{
    /**
     * @var \Intervention\Image\Image
     */
    private ImageInterface $image;

    /**
     * @var int
     */
    private int $quality;

    /**
     * @var bool
     */
    private bool $interlace;

    /**
     * @var bool
     */
    private bool $upsize;

    /**
     * Image format (jpg, gif, png, webp).
     *
     * @var string
     */
    private string $format;

    public function __construct(string $path, array $options = [])
    {
        $manager = new ImageManager(new Driver());
        $this->image = $manager->read($path);
        $this->interlace = $options['interlace'];
        $this->upsize = $options['upsize'];
        if (isset($options['quality']) && is_array($options['quality'])) {
            $this->quality = reset($options['quality']);
        } else {
            $this->quality = $options['quality'];
        }
        $this->format = $options['format'] ?? $this->getFormatFromPath($path);
    }

    /**
     * Take the input from the URL and apply transformations on the image.
     */
    public function process(?int $width, ?int $height, array $options = []): self
    {
        $this->trim($options)
            ->resizeAndOrCrop($width, $height, $options)
            ->applyFilters($options);

        return $this;
    }

    /**
     * Determine which trim to apply.
     */
    public function trim(array $options): self
    {
        if (isset($options['trim'])) {
            return $this->trimPixels($options['trim']);
        }
        if (isset($options['trim_perc'])) {
            return $this->trimPerc($options['trim_perc']);
        }

        return $this;
    }

    /**
     * Trim the source before applying the crop with as offset pixels.
     */
    public function trimPixels(array $coords): self
    {
        list($x1, $y1, $x2, $y2) = $coords;
        $width = $x2 - $x1;
        $height = $y2 - $y1;
        $this->image->crop($width, $height, $x1, $y1);

        return $this;
    }

    /**
     * Trim the source before applying the crop with offset percentages.
     */
    public function trimPerc(array $coords): self
    {
        list($x1, $y1, $x2, $y2) = $coords;
        $imgWidth = $this->image->width();
        $imgHeight = $this->image->height();
        $x = (int) round($x1 * $imgWidth);
        $y = (int) round($y1 * $imgHeight);
        $width = (int) round($x2 * $imgWidth - $x);
        $height = (int) round($y2 * $imgHeight - $y);
        $this->image->crop($width, $height, $x, $y);

        return $this;
    }

    /**
     * Determine which resize and crop to apply.
     */
    public function resizeAndOrCrop(?int $width, ?int $height, array $options = []): self
    {
        if (!$width && !$height) {
            return $this;
        }
        if (isset($options['quadrant'])) {
            return $this->cropQuadrant($width, $height, $options);
        }
        if (array_key_exists('pad', $options)) {
            $this->pad($width, $height, $options);
        }
        if (array_key_exists('resize', $options) || !$width || !$height) {
            return $this->resize($width, $height);
        }

        return $this->crop($width, $height);
    }

    /**
     * Do a quadrant adaptive resize.  Supported quadrant values are:
     * +---+---+---+
     * |   | T |   |
     * +---+---+---+
     * | L | C | R |
     * +---+---+---+
     * |   | B |   |
     * +---+---+---+.
     *
     * @throws Exception
     */
    public function cropQuadrant(?int $width, ?int $height, array $options): self
    {
        if (!$height || !$width) {
            throw new Exception('Croppa: Qudrant option needs width and height');
        }
        if (empty($options['quadrant'][0])) {
            throw new Exception('Croppa:: No quadrant specified');
        }
        $quadrant = mb_strtoupper($options['quadrant'][0]);
        if (!in_array($quadrant, ['T', 'L', 'C', 'R', 'B'])) {
            throw new Exception('Croppa:: Invalid quadrant');
        }
        $positions = [
            'T' => 'top',
            'L' => 'left',
            'C' => 'center',
            'R' => 'right',
            'B' => 'bottom',
        ];
        if (!$this->upsize) {
            $this->image->coverDown($width, $height, $positions[$quadrant]);
        } else {
            $this->image->cover($width, $height, $positions[$quadrant]);
        }

        return $this;
    }

    /**
     * Resize with no cropping.
     */
    public function resize(?int $width, ?int $height): self
    {
        if (!$this->upsize) {
            $this->image->scaleDown($width, $height);
        } else {
            $this->image->scale($width, $height);
        }

        return $this;
    }

    /**
     * Resize and crop.
     */
    public function crop(?int $width, ?int $height): self
    {
        if (!$this->upsize) {
            $this->image->coverDown($width, $height);
        } else {
            $this->image->cover($width, $height);
        }

        return $this;
    }

    /**
     * Pad an image to desired dimensions.
     * Moves and resize the image into the center and fills the rest with given color.
     */
    public function pad(?int $width, ?int $height, array $options): self
    {
        if (!$height || !$width) {
            throw new Exception('Croppa: Pad option needs width and height');
        }
        if (!isset($options['pad'])) {
            throw new Exception('Croppa: No pad color specified');
        }
        $rgbArray = $options['pad'] ?: [255, 255, 255];
        $color = sprintf("#%02x%02x%02x", $rgbArray[0], $rgbArray[1], $rgbArray[2]);

        if (!$this->upsize) {
            $this->image->scaleDown($width, $height);
        } else {
            $this->image->scale($width, $height);
        }

        $this->image->resizeCanvas($width, $height, $color, 'center');

        return $this;
    }

    /**
     * Apply filters that have been defined in the config as seperate classes.
     */
    public function applyFilters(array $options): self
    {
        if (isset($options['filters']) && is_array($options['filters'])) {
            array_map(function ($filter) {
                $this->image = $filter->applyFilter($this->image);
            }, $options['filters']);
        }

        return $this;
    }

    private function getFormatFromPath(string $path): string
    {
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'gif':
                return 'gif';

            case 'png':
                return 'png';

            case 'webp':
                return 'webp';

            default:
                return 'jpg';
        }
    }

    /**
     * Get the image data.
     */
    public function get(): string
    {
        return $this->image->encodeByExtension($this->format, progressive: $this->interlace, quality: $this->quality);
    }
}
