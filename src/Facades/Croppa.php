<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Facades;

use Bkwld\Croppa\Helpers;
use Illuminate\Support\Facades\Facade;

class Croppa extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Helpers::class;
    }
}
