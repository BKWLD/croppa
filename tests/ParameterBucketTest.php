<?php

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\Exception;
use Bkwld\Croppa\Filters\BlackWhite;
use Bkwld\Croppa\Filters\Blur;
use Bkwld\Croppa\ParameterBucket;
use Bkwld\Croppa\Test\Traits\BindsUrl;

class ParameterBucketTest extends TestCase
{
    use BindsUrl;

    public function setUp(): void
    {
        parent::setUp();
        $this->bindUrl();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCreateFrom(): void
    {
        $bucket = ParameterBucket::createFrom(
            'uploads/some/dir/file-200x100.jpg'
        );
        $this->assertEquals('some/dir/file.jpg', $bucket->getPath());
        $this->assertEquals(200, $bucket->getWidth());
        $this->assertEquals(100, $bucket->getHeight());
        $this->assertEquals([], $bucket->getUrlOptions());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCreateFromWithFilter(): void
    {
        $bucket = ParameterBucket::createFrom(
            'uploads/some/dir/file-200x100-filters(gray).jpg'
        );
        $this->assertEquals('some/dir/file.jpg', $bucket->getPath());
        $this->assertEquals(200, $bucket->getWidth());
        $this->assertEquals(100, $bucket->getHeight());
        $this->assertEquals(['filters' => [new BlackWhite()]],
            $bucket->getUrlOptions());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCreateFromWithMultipleFilters(): void
    {
        $bucket = ParameterBucket::createFrom(
            'uploads/some/dir/file-200x100-filters(gray,blur).jpg'
        );
        $this->assertEquals('some/dir/file.jpg', $bucket->getPath());
        $this->assertEquals(200, $bucket->getWidth());
        $this->assertEquals(100, $bucket->getHeight());
        $this->assertEquals(['filters' => [new BlackWhite(), new Blur()]],
            $bucket->getUrlOptions());
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCreateFromWithMultipleFiltersAndOptions(): void
    {
        $bucket = ParameterBucket::createFrom(
            'uploads/some/dir/file-200x100-filters(gray,blur)-quality(50).jpg'
        );
        $this->assertEquals('some/dir/file.jpg', $bucket->getPath());
        $this->assertEquals(200, $bucket->getWidth());
        $this->assertEquals(100, $bucket->getHeight());
        $this->assertEquals(
            [
                'filters' => [new BlackWhite(), new Blur()],
                'quality' => 50,
            ],
            $bucket->getUrlOptions()
        );
    }
}
