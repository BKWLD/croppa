<?php

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Image;

class Negative implements FilterInterface
{
    public function applyFilter(Image $thumb): Image
    {
        return $thumb->invert();
    }
}
