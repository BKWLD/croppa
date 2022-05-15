<?php

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\DirectoryListing;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TestTooManyCrops extends TestCase
{
    private $dir;

    public function setUp(): void
    {
        parent::setUp();

        // Mock flysystem
        $this->dir = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                ['path' => 'me.jpg'],
                ['path' => 'me-too.jpg'],
                ['path' => 'me-200x100.jpg'],
                ['path' => 'me-200x200.jpg'],
                ['path' => 'me-200x300.jpg'],
                ['path' => 'unrelated.jpg'],
            ]))
            ->getMock();
    }

    public function testListCrops()
    {
        $storage = new Storage();
        $storage->setCropsDisk($this->dir);
        $this->assertEquals([
            'me-200x100.jpg',
            'me-200x200.jpg',
            'me-200x300.jpg',
        ], $storage->listCrops('me.jpg'));
    }

    public function testAcceptableNumber()
    {
        $storage = new Storage(null, ['max_crops' => 4]);
        $storage->setCropsDisk($this->dir);
        $this->assertFalse($storage->tooManyCrops('me.jpg'));
    }

    public function testTooMany()
    {
        $storage = new Storage(['max_crops' => 3]);
        $storage->setCropsDisk($this->dir);
        $this->assertTrue($storage->tooManyCrops('me.jpg'));
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
