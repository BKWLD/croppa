<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Interfaces\ImageInterface;

class Negative implements FilterInterface
{
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        return $image->invert();
    }
}
