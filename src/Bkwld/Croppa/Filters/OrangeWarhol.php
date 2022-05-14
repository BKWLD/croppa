<?php

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Image;

class OrangeWarhol implements FilterInterface
{
    public function applyFilter(Image $thumb): Image
    {
        return $thumb->greyscale()->brightness(50)->colorize(-10, -70, -100);
    }
}
