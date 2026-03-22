<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Interfaces\ImageInterface;

class OrangeWarhol implements FilterInterface
{
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        return $image->greyscale()->brightness(50)->colorize(-10, -70, -100);
    }
}
