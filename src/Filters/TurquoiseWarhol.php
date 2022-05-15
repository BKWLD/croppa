<?php

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Image;

class TurquoiseWarhol implements FilterInterface
{
    public function applyFilter(Image $thumb): Image
    {
        return $thumb->greyscale()->brightness(50)->colorize(-70, -10, -20);
    }
}
