<?php

namespace Bkwld\Croppa\Test\Traits;

use Bkwld\Croppa\Config;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait GetsConfig
{
    /**
     * @throws BindingResolutionException
     */
    public function getConfig(): array
    {
        $app = app();
        $config = new Config($app);
        $configArray = $config->get();

        $configArray['src_disk'] = 'fake_disk';
        $configArray['crops_disk'] = 'fake_disk';
        $configArray['path'] = 'uploads/(.*)$';

        return $configArray;
    }
}
