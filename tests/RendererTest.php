<?php

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\Exception;
use Bkwld\Croppa\Renderer;
use Bkwld\Croppa\Storage;
use Bkwld\Croppa\Test\Traits\BindsUrl;
use Bkwld\Croppa\Test\Traits\GetsConfig;
use Bkwld\Croppa\URL;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\UploadedFile;

/**
 *
 */
class RendererTest extends TestCase
{
    use BindsUrl;
    use GetsConfig;

    protected function generateFakeImage(): void
    {
        $fileName = 'test.png';
        $file = UploadedFile::fake()
            ->image($fileName, 100, 100);

        $this->defaultDisk->putFileAs(
            '/',
            $file,
            $fileName
        );
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->bindUrl();

        $this->url = $this->app->make(URL::class);
        $config = $this->getConfig();
        $storage = new Storage($config);

        $this->generateFakeImage();

        $this->renderer = new Renderer(
            $this->url,
            $storage,
            $config
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRender(): void
    {
        $url = $this->url->generate(
            'uploads/test.png',
            50,
            50
        );

        $this->assertEquals(
            'image/png',
            $this->defaultDisk->mimeType('test.png')
        );

        $cropPath = $this->renderer->render($url);
        $this->assertStringContainsString(
            'test-50x50.png',
            $cropPath
        );

        $this->assertTrue(
            $this->defaultDisk->exists($cropPath)
        );

        $this->assertEquals(
            'image/png',
            $this->defaultDisk->mimeType($cropPath)
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRenderWithForcedJpegFormat(): void
    {
        $url = $this->url->generate(
            'uploads/test.png',
            50,
            50,
            ['format' => 'jpg']
        );

        $this->assertEquals(
            'image/png',
            $this->defaultDisk->mimeType('test.png')
        );

        $cropPath = $this->renderer->render($url);
        $this->assertStringContainsString(
            'test-50x50-format(jpg).jpg',
            $cropPath
        );

        $this->assertTrue(
            $this->defaultDisk->exists($cropPath)
        );

        $this->assertEquals(
            'image/jpeg',
            $this->defaultDisk->mimeType($cropPath)
        );
    }

    /**
     * When crops are not remote, they are always rendered and stored
     * @return void
     * @throws Exception
     */
    public function testShouldNotReturnExistingCrop(): void
    {
        $url = $this->url->generate(
            'uploads/test.png',
            50,
            50,
            ['format' => 'jpg']
        );
        $cropPath = $this->renderer->render($url);

        $this->assertFalse(
            $this->renderer->shouldReturnExistingCrop($cropPath)
        );
    }

    public function testReplaceOriginalFileSuffix(): void
    {
        $url = 'uploads/test.png';

        $this->assertEquals(
            'uploads/test.jpg',
            $this->renderer->replaceOriginalFileSuffix($url, 'jpg')
        );

        $url = 'uploads/test.jpg.png';

        $this->assertEquals(
            'uploads/test.jpg.jpg',
            $this->renderer->replaceOriginalFileSuffix($url, 'jpg')
        );

        $url = 'uploads/test.jpg.jpg';

        $this->assertEquals(
            'uploads/test.jpg.jpg',
            $this->renderer->replaceOriginalFileSuffix($url, 'jpg')
        );

        $url = $this->url->generate(
            'uploads/test.png',
            50,
            50,
            ['format' => 'jpg']
        );

        $this->assertStringContainsString(
            'uploads/test-50x50-format(jpg).jpg',
            $this->renderer->replaceOriginalFileSuffix($url, 'jpg')
        );
    }
}
