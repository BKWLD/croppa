<?php

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Image;

class BlackWhite implements FilterInterface
{
    public function applyFilter(Image $thumb): Image
    {
        return $thumb->greyscale();
    }
}
