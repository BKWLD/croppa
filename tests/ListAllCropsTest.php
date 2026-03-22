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
final class ListAllCropsTest extends TestCase
{
    private FilesystemAdapter $src_disk;

    private FilesystemAdapter $crops_disk;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock src dir
        $this->src_disk = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('fileExists')->with('01/me.jpg')->andReturn(true)
            ->shouldReceive('fileExists')->with('02/another.jpg')->andReturn(true)
            ->shouldReceive('fileExists')->with('03/ignore.jpg')->andReturn(false)
            ->getMock();

        // Mock crops dir
        $this->crops_disk = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                new FileAttributes('01/me.jpg'),
                new FileAttributes('01/me-too.jpg'),
                new FileAttributes('01/me-200x100.jpg'),
                new FileAttributes('01/me-200x200.jpg'),
                new FileAttributes('01/me-200x300.jpg'),

                // Stored in another src dir
                new FileAttributes('02/another.jpg'),
                new FileAttributes('02/another-200x300.jpg'),
                new FileAttributes('02/unrelated.jpg'),

                // Not a crop cause there is no corresponding source file
                new FileAttributes('03/ignore-200x200.jpg'),
            ]))
            ->getMock();
    }

    public function test_all(): void
    {
        $storage = new Storage;
        $storage->setSrcDisk($this->src_disk);
        $storage->setCropsDisk($this->crops_disk);
        $this->assertEquals([
            '01/me-200x100.jpg',
            '01/me-200x200.jpg',
            '01/me-200x300.jpg',
            '02/another-200x300.jpg',
        ], $storage->listAllCrops());
    }

    public function test_filtered(): void
    {
        $storage = new Storage;
        $storage->setSrcDisk($this->src_disk);
        $storage->setCropsDisk($this->crops_disk);
        $this->assertEquals([
            '02/another-200x300.jpg',
        ], $storage->listAllCrops('^02/'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
