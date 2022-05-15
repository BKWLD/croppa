<?php

namespace Bkwld\Croppa\Facades;

use Illuminate\Support\Facades\Facade;

class Croppa extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Bkwld\Croppa\Helpers';
    }
}
