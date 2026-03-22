<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Interfaces\ImageInterface;

class TurquoiseWarhol implements FilterInterface
{
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        return $image->greyscale()->brightness(50)->colorize(-70, -10, -20);
    }
}
