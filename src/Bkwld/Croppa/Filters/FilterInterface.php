<?php

namespace Bkwld\Croppa\Filters;

use GdThumb;

interface FilterInterface
{
    /**
     * @return \GdThumb $thumb
     */
    public function applyFilter(GdThumb $thumb);
}
