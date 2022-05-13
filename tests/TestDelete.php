<?php

use Bkwld\Croppa\Helpers;
use Bkwld\Croppa\Storage;
use Bkwld\Croppa\URL;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\DirectoryListing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TestDelete extends TestCase
{
    public function testDeleteSrc()
    {
        $disk = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn([
                ['path' => 'me.jpg'],
                ['path' => 'me-200x100.jpg'],
                ['path' => 'me-200x200.jpg'],
                ['path' => 'me-200x300.jpg'],
                ['path' => 'unrelated.jpg'],
            ])
            ->shouldReceive('delete')
            ->withAnyArgs()
            ->once()
            ->getMock();

        $storage = new Storage();
        $storage->setSrcDisk($disk);
        $this->assertNull($storage->deleteSrc('me.jpg'));
    }

    public function testDeleteCrops()
    {
        $disk = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                ['path' => 'me.jpg'],
                ['path' => 'me-200x100.jpg'],
                ['path' => 'me-200x200.jpg'],
                ['path' => 'me-200x300.jpg'],
                ['path' => 'unrelated.jpg'],
            ]))
            ->shouldReceive('delete')
            ->withAnyArgs()
            ->times(3)
            ->getMock();

        $storage = new Storage();
        $storage->setCropsDisk($disk);
        $this->assertEquals([
            'me-200x100.jpg',
            'me-200x200.jpg',
            'me-200x300.jpg',
        ], $storage->deleteCrops('me.jpg'));
    }

    // Instantiate a helpers instance using mocked disks so the whole delete
    // logic can be checked
    private function mockHelpersForDeleting()
    {
        // The path is to a sub dir
        $url = new URL([
            'path' => 'uploads/(?:thumbs/)?(.*)$',
        ]);

        $src = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                ['path' => 'me.jpg'],
                ['path' => 'unrelated.jpg'],
            ]))
            ->shouldReceive('delete')
            ->withAnyArgs()
            ->once()
            ->getMock();

        $crops = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                ['path' => 'me-200x100.jpg'],
                ['path' => 'me-200x200.jpg'],
                ['path' => 'me-200x300.jpg'],
                ['path' => 'unrelated.jpg'],
            ]))
            ->shouldReceive('delete')
            ->withAnyArgs()
            ->times(3)
            ->getMock();

        $storage = new Storage();
        $storage->setSrcDisk($src);
        $storage->setCropsDisk($crops);

        $handler = Mockery::mock('Bkwld\Croppa\Handler');

        return new Helpers($url, $storage, $handler);
    }

    public function testDeleteCropsInSubDir()
    {
        $helpers = $this->mockHelpersForDeleting();
        $helpers->delete('/uploads/me.jpg');
    }

    public function testDeleteCropsInSubDirWithFullURL()
    {
        $helpers = $this->mockHelpersForDeleting();
        $helpers->delete('http://domain.com/uploads/me.jpg');
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
