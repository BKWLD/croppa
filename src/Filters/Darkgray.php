<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Interfaces\ImageInterface;

class Darkgray implements FilterInterface
{
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        return $image->greyscale()->colorize(-50, -50, -50);
    }
}
