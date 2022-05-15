<?php

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Image;

class Blur implements FilterInterface
{
    public function applyFilter(Image $thumb): Image
    {
        return $thumb->blur();
    }
}
