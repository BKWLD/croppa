<?php

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\URL;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TestUrlGenerator extends TestCase
{
    public function testWidthAndHeight()
    {
        $url = new URL();
        $this->assertEquals('/path/file-200x100.png', $url->generate('/path/file.png', 200, 100));
    }

    public function testIgnore()
    {
        $url = new URL(['ignore' => '\.(?:gif|GIF)$']);
        $this->assertEquals('/path/file.gif', $url->generate('/path/file.gif', 200, 100));
        $this->assertEquals('/path/file-200x100.png', $url->generate('/path/file.png', 200, 100));
    }

    public function testNoWidthOrHeight()
    {
        $url = new URL();
        $this->assertEquals('/path/file.png', $url->generate('/path/file.png'));
    }

    public function testNoWidth()
    {
        $url = new URL();
        $this->assertEquals('/path/file-_x100.png', $url->generate('/path/file.png', null, 100));
    }

    public function testNoHeight()
    {
        $url = new URL();
        $this->assertEquals('/path/file-200x_.png', $url->generate('/path/file.png', 200));
    }

    public function testResize()
    {
        $url = new URL();
        $this->assertEquals('/path/file-200x100-resize.png', $url->generate('/path/file.png', 200, 100, ['resize']));
    }

    public function testQuadrant()
    {
        $url = new URL();
        $this->assertEquals('/path/file-200x100-quadrant(T).png', $url->generate('/path/file.png', 200, 100, ['quadrant' => 'T']));
    }

    public function testHostInSrc()
    {
        $url = new URL();
        $this->assertEquals('/path/file-200x_.png', $url->generate('http://domain.tld/path/file.png', 200));
        $this->assertEquals('/path/file-200x_.png', $url->generate('https://domain.tld/path/file.png', 200));
    }

    public function testSecure()
    {
        $url = new URL(['signing_key' => 'test']);
        $this->assertEquals('/path/file-200x100.png?token=dc0787d205f619a2b2df8554c960072e', $url->generate('/path/file.png', 200, 100));

        $url = new URL(['signing_key' => 'test']);
        $this->assertNotEquals('/path/file-200x100.png?token=dc0787d205f619a2b2df8554c960072e', $url->generate('/path/file.png', 200, 200));
    }
}
