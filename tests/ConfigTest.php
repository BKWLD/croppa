<?php

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\Config;
use Illuminate\Contracts\Container\BindingResolutionException;

class ConfigTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testGet()
    {
        $config = new Config($this->app);
        $configArray = $config->get();
        $this->assertIsArray($configArray);

        $this->assertArrayHasKey('src_disk', $configArray);
        $this->assertArrayHasKey('crops_disk', $configArray);
        $this->assertArrayHasKey('path', $configArray);
        $this->assertArrayHasKey('interlace', $configArray);
        $this->assertArrayHasKey('filters', $configArray);
    }
}
