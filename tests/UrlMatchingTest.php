<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\URL;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UrlMatchingTest extends TestCase
{
    private URL $url;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = new URL([
            'path' => 'uploads/(.*)$',
        ]);
    }

    /**
     * This mimics the Illuminate\Routing\Matching\UriValidator compiled regex
     * https://regex101.com/r/xS3nQ2/1.
     *
     * @param  mixed  $path
     */
    public function match(string $path): bool
    {
        // The compiled regex is wrapped like this
        $pattern = '#^\/(?P<path>'.$this->url->routePattern().')$#s';

        // UriValidator prepends a slash
        return preg_match($pattern, '/'.$path) > 0;
    }

    public function test_no_params(): void
    {
        $this->assertFalse($this->match('uploads/1/2/file.jpg'));
    }

    public function test_ourside_dir(): void
    {
        $this->assertFalse($this->match('assets/1/2/file.jpg'));
        $this->assertFalse($this->match('apple-touch-icon-152x152-precomposed.png'));
    }

    public function test_width(): void
    {
        $this->assertTrue($this->match('uploads/1/2/file-200x_.jpg'));
    }

    public function test_height(): void
    {
        $this->assertTrue($this->match('uploads/1/2/file-_x100.jpg'));
    }

    public function test_width_and_height(): void
    {
        $this->assertTrue($this->match('uploads/1/2/file-200x100.jpg'));
    }

    public function test_width_and_height_and_options(): void
    {
        $this->assertTrue($this->match('uploads/1/2/file-200x100-quadrant(T).jpg'));
    }
}
