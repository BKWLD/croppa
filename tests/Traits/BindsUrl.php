<?php

namespace Bkwld\Croppa\Test\Traits;

use Bkwld\Croppa\URL;
use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait BindsUrl
{
    use GetsConfig;

    public function bindUrl(): void
    {
        app()->singleton(URL::class, function () {
            return new URL($this->getConfig());
        });
    }
}
