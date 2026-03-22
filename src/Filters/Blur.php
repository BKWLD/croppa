<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Interfaces\ImageInterface;

class Blur implements FilterInterface
{
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        return $image->blur();
    }
}
