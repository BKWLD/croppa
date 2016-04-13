<?php

namespace Bkwld\Croppa\Filters;

use GdThumb;

interface FilterInterface
{
    /**
     * @param \GdThumb $thumb
     *
     * @return \GdThumb $thumb
     */
    public function applyFilter(GdThumb $thumb);
}
