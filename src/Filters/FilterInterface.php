<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Filters;

use Intervention\Image\Interfaces\ImageInterface;

interface FilterInterface
{
    public function applyFilter(ImageInterface $image): ImageInterface;
}
