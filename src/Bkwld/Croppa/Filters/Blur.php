<?php

namespace Bkwld\Croppa\Filters;

use GdThumb;

class Blur implements FilterInterface
{
    /**
     * Applies filter to given thumbnail object.
     *
     * @return \Intervention\Image\Image
     */
    public function applyFilter(GdThumb $thumb)
    {
        return $thumb->imageFilter(IMG_FILTER_GAUSSIAN_BLUR);
    }
}
