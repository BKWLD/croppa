<?php

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\CroppaServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage as IlluminateStorage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected Filesystem $defaultDisk;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        $this->cleanDisk();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            CroppaServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getEnvironmentSetUp($app): void
    {
        $this->setUpTemporaryDisk($app);
    }

    /**
     * @param Application $app
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUpTemporaryDisk(Application $app): void
    {
        /** @var Repository|Application|mixed $config */
        $config = $app->get('config');
        // Configure a fake_disk disk
        $config->set('filesystems.disks.fake_disk', [
            'driver' => 'local',
            'root' => storage_path('app/fake-disk'),
            'throw' => false,
        ]);

        // Set the default disk to the fake_disk disk
        $config->set('filesystems.default', 'fake_disk');

        $this->defaultDisk = IlluminateStorage::disk('fake_disk');
        $app->singleton('fake_disk', function () {
            return $this->defaultDisk;
        });
    }


    protected function cleanDisk() {
        $this->defaultDisk->deleteDirectory('/');
    }
}
