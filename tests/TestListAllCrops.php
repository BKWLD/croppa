<?php

use Bkwld\Croppa\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\DirectoryListing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TestListAllCrops extends TestCase
{
    /**
     * @var FilesystemAdapter
     */
    protected $src_dir;

    /**
     * @var FilesystemAdapter
     */
    protected $crops_dir;

    public function setUp(): void
    {
        // Mock src dir
        $this->src_dir = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('fileExists')->with('01/me.jpg')->andReturn(true)
            ->shouldReceive('fileExists')->with('02/another.jpg')->andReturn(true)
            ->shouldReceive('fileExists')->with('03/ignore.jpg')->andReturn(false)
            ->getMock();

        // Mock crops dir
        $this->crops_dir = Mockery::mock(FilesystemAdapter::class)
            ->shouldReceive('listContents')
            ->withAnyArgs()
            ->andReturn(new DirectoryListing([
                ['path' => '01/me.jpg'],
                ['path' => '01/me-too.jpg'],
                ['path' => '01/me-200x100.jpg'],
                ['path' => '01/me-200x200.jpg'],
                ['path' => '01/me-200x300.jpg'],

                // Stored in another src dir
                ['path' => '02/another.jpg'],
                ['path' => '02/another-200x300.jpg'],
                ['path' => '02/unrelated.jpg'],

                // Not a crop cause there is no corresponding source file
                ['path' => '03/ignore-200x200.jpg'],
            ]))
            ->getMock();
    }

    public function testAll()
    {
        $storage = new Storage();
        $storage->setSrcDisk($this->src_dir);
        $storage->setCropsDisk($this->crops_dir);
        $this->assertEquals([
            '01/me-200x100.jpg',
            '01/me-200x200.jpg',
            '01/me-200x300.jpg',
            '02/another-200x300.jpg',
        ], $storage->listAllCrops());
    }

    public function testFiltered()
    {
        $storage = new Storage();
        $storage->setSrcDisk($this->src_dir);
        $storage->setCropsDisk($this->crops_dir);
        $this->assertEquals([
            '02/another-200x300.jpg',
        ], $storage->listAllCrops('^02/'));
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
