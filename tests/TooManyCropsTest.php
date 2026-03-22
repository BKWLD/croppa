<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TooManyCropsTest extends TestCase
{
    private $dir;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock flysystem
        $this->dir = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                new FileAttributes('me.jpg'),
                new FileAttributes('me-too.jpg'),
                new FileAttributes('me-200x100.jpg'),
                new FileAttributes('me-200x200.jpg'),
                new FileAttributes('me-200x300.jpg'),
                new FileAttributes('unrelated.jpg'),
            ]))
            ->getMock();
    }

    public function test_list_crops(): void
    {
        $storage = new Storage;
        $storage->setCropsDisk($this->dir);
        $this->assertEquals([
            'me-200x100.jpg',
            'me-200x200.jpg',
            'me-200x300.jpg',
        ], $storage->listCrops('me.jpg'));
    }

    public function test_acceptable_number(): void
    {
        $storage = new Storage(null, ['max_crops' => 4]);
        $storage->setCropsDisk($this->dir);
        $this->assertFalse($storage->tooManyCrops('me.jpg'));
    }

    public function test_too_many(): void
    {
        $storage = new Storage(['max_crops' => 3]);
        $storage->setCropsDisk($this->dir);
        $this->assertTrue($storage->tooManyCrops('me.jpg'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
