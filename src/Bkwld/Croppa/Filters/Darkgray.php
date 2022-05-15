<?php

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Image;

class Darkgray implements FilterInterface
{
    public function applyFilter(Image $thumb): Image
    {
        return $thumb->greyscale()->colorize(-50, -50, -50);
    }
}
