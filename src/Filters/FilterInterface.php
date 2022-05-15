<?php

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Image;

interface FilterInterface
{
    public function applyFilter(Image $thumb): Image;
}
