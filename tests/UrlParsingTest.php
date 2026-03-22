<?php

declare(strict_types=1);

namespace Bkwld\Croppa\Test;

use Bkwld\Croppa\Filters\BlackWhite;
use Bkwld\Croppa\Filters\Blur;
use Bkwld\Croppa\Filters\Darkgray;
use Bkwld\Croppa\Filters\Negative;
use Bkwld\Croppa\Filters\OrangeWarhol;
use Bkwld\Croppa\Filters\TurquoiseWarhol;
use Bkwld\Croppa\URL;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UrlParsingTest extends TestCase
{
    private URL $url;

    protected function setUp(): void
    {
        parent::setUp();

        $this->url = new URL([
            'path' => 'uploads/(.*)$',
            'filters' => [
                'gray' => BlackWhite::class,
                'darkgray' => Darkgray::class,
                'blur' => Blur::class,
                'negative' => Negative::class,
                'orange' => OrangeWarhol::class,
                'turquoise' => TurquoiseWarhol::class,
            ],
        ]);
    }

    public function test_no_params(): void
    {
        $this->assertFalse($this->url->parse('uploads/1/2/file.jpg'));
    }

    public function test_width(): void
    {
        $this->assertEquals([
            '1/2/file.jpg', 200, null, [],
        ], $this->url->parse('uploads/1/2/file-200x_.jpg'));
    }

    public function test_height(): void
    {
        $this->assertEquals([
            '1/2/file.jpg', null, 100, [],
        ], $this->url->parse('uploads/1/2/file-_x100.jpg'));
    }

    public function test_width_and_height(): void
    {
        $this->assertEquals([
            '1/2/file.jpg', 200, 100, [],
        ], $this->url->parse('uploads/1/2/file-200x100.jpg'));
    }

    public function test_width_and_height_and_options(): void
    {
        $this->assertEquals([
            '1/2/file.jpg', 200, 100, ['resize' => null],
        ], $this->url->parse('uploads/1/2/file-200x100-resize.jpg'));
    }

    public function test_width_and_height_and_options_with_value(): void
    {
        $this->assertEquals([
            '1/2/file.jpg', 200, 100, ['quadrant' => ['T']],
        ], $this->url->parse('uploads/1/2/file-200x100-quadrant(T).jpg'));
    }

    public function test_width_and_height_and_options_with_value_list(): void
    {
        $this->assertEquals([
            '1/2/file.jpg', 200, 100, ['trim_perc' => [0.25, 0.25, 0.75, 0.75]],
        ], $this->url->parse('uploads/1/2/file-200x100-trim_perc(0.25,0.25,0.75,0.75).jpg'));
    }

    public function test_filters(): void
    {
        $this->assertEquals([
            '1/2/file.jpg', 200, 100, ['filters' => [
                new Blur,
                new Negative,
            ]],
        ], $this->url->parse('uploads/1/2/file-200x100-filters(blur,negative).jpg'));
    }

    public function test_crops_in_sub_directory(): void
    {
        $url = new URL([
            'path' => 'images/(?:crops/)?(.*)$',
        ]);
        $this->assertEquals([
            'file.jpg', 200, 100, [],
        ], $url->parse('images/crops/file-200x100.jpg'));
    }
}
