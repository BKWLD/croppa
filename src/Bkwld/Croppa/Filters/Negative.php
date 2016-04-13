<?php

namespace Bkwld\Croppa\Filters;

use GdThumb;

class Negative implements FilterInterface
{
    /**
     * Applies filter to given thumbnail object.
     *
     * @param \GdThumb $thumb
     *
     * @return \Intervention\Image\Image
     */
    public function applyFilter(GdThumb $thumb)
    {
        $thumb->imageFilter(IMG_FILTER_NEGATE);
        $thumb->imageFilter(IMG_FILTER_CONTRAST, -50);

        return $thumb;
    }

}
