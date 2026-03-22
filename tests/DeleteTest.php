<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\Handler;
use Bkwld\Croppa\Helpers;
use Bkwld\Croppa\Storage;
use Bkwld\Croppa\URL;
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
final class DeleteTest extends TestCase
{
    public function test_delete_src(): void
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

        $storage = new Storage;
        $storage->setSrcDisk($disk);
        $this->assertNull($storage->deleteSrc('me.jpg'));
    }

    public function test_delete_crops(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                new FileAttributes('me.jpg'),
                new FileAttributes('me-200x100.jpg'),
                new FileAttributes('me-200x200.jpg'),
                new FileAttributes('me-200x300.jpg'),
                new FileAttributes('unrelated.jpg'),
            ]))
            ->shouldReceive('delete')
            ->withAnyArgs()
            ->times(3)
            ->getMock();

        $storage = new Storage;
        $storage->setCropsDisk($disk);
        $this->assertEquals([
            'me-200x100.jpg',
            'me-200x200.jpg',
            'me-200x300.jpg',
        ], $storage->deleteCrops('me.jpg'));
    }

    // Instantiate a helpers instance using mocked disks so the whole delete
    // logic can be checked
    private function mockHelpersForDeleting(): Helpers
    {
        // The path is to a sub dir
        $url = new URL([
            'path' => 'uploads/(?:thumbs/)?(.*)$',
        ]);

        $src = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                new FileAttributes('me.jpg'),
                new FileAttributes('unrelated.jpg'),
            ]))
            ->shouldReceive('delete')
            ->withAnyArgs()
            ->once()
            ->getMock();

        $crops = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                new FileAttributes('me-200x100.jpg'),
                new FileAttributes('me-200x200.jpg'),
                new FileAttributes('me-200x300.jpg'),
                new FileAttributes('unrelated.jpg'),
            ]))
            ->shouldReceive('delete')
            ->withAnyArgs()
            ->times(3)
            ->getMock();

        $storage = new Storage;
        $storage->setSrcDisk($src);
        $storage->setCropsDisk($crops);

        $mock = Mockery::mock(Handler::class);

        return new Helpers($url, $storage, $mock);
    }

    public function test_delete_crops_in_sub_dir(): void
    {
        $this->expectNotToPerformAssertions();
        $helpers = $this->mockHelpersForDeleting();
        $helpers->delete('/uploads/me.jpg');
    }

    public function test_delete_crops_in_sub_dir_with_full_url(): void
    {
        $this->expectNotToPerformAssertions();
        $helpers = $this->mockHelpersForDeleting();
        $helpers->delete('http://domain.com/uploads/me.jpg');
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
