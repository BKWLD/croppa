<?php

namespace Bkwld\Croppa\Filters;

use GdThumb;

class Darkgray implements FilterInterface
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
        $thumb->imageFilter(IMG_FILTER_GRAYSCALE);
        $thumb->imageFilter(IMG_FILTER_COLORIZE, -80, -80, -80);
        return $thumb;
    }

}
