<?php

namespace Bkwld\Croppa\Filters;

use GdThumb;

class OrangeWarhol implements FilterInterface
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
        $thumb->imageFilter(IMG_FILTER_BRIGHTNESS, 80);
        $thumb->imageFilter(IMG_FILTER_COLORIZE, -30, -143, -255);

        return $thumb;
    }


}
