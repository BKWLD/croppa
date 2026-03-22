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
final class UrlGeneratorTest extends TestCase
{
    public function test_width_and_height(): void
    {
        $url = new URL;
        $this->assertEquals('/path/file-200x100.png', $url->generate('/path/file.png', 200, 100));
    }

    public function test_ignore(): void
    {
        $url = new URL(['ignore' => '\.(?:gif|GIF)$']);
        $this->assertEquals('/path/file.gif', $url->generate('/path/file.gif', 200, 100));
        $this->assertEquals('/path/file-200x100.png', $url->generate('/path/file.png', 200, 100));
    }

    public function test_no_width_or_height(): void
    {
        $url = new URL;
        $this->assertEquals('/path/file.png', $url->generate('/path/file.png'));
    }

    public function test_no_width(): void
    {
        $url = new URL;
        $this->assertEquals('/path/file-_x100.png', $url->generate('/path/file.png', null, 100));
    }

    public function test_no_height(): void
    {
        $url = new URL;
        $this->assertEquals('/path/file-200x_.png', $url->generate('/path/file.png', 200));
    }

    public function test_resize(): void
    {
        $url = new URL;
        $this->assertEquals('/path/file-200x100-resize.png', $url->generate('/path/file.png', 200, 100, ['resize']));
    }

    public function test_quadrant(): void
    {
        $url = new URL;
        $this->assertEquals('/path/file-200x100-quadrant(T).png', $url->generate('/path/file.png', 200, 100, ['quadrant' => 'T']));
    }

    public function test_host_in_src(): void
    {
        $url = new URL;
        $this->assertEquals('/path/file-200x_.png', $url->generate('http://domain.tld/path/file.png', 200));
        $this->assertEquals('/path/file-200x_.png', $url->generate('https://domain.tld/path/file.png', 200));
    }

    public function test_secure(): void
    {
        $url = new URL(['signing_key' => 'test']);
        $this->assertEquals('/path/file-200x100.png?token=dc0787d205f619a2b2df8554c960072e', $url->generate('/path/file.png', 200, 100));

        $url = new URL(['signing_key' => 'test']);
        $this->assertNotEquals('/path/file-200x100.png?token=dc0787d205f619a2b2df8554c960072e', $url->generate('/path/file.png', 200, 200));
    }
}
