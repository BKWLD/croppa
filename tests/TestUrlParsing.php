<?php

use Bkwld\Croppa\URL;

class TestUrlParsing extends PHPUnit_Framework_TestCase {

	private $url;
	public function setUp() {
		$this->url = new URL([ 'path' => 'uploads/(.*)$', ]);
	}

	public function testNoParams() {
		$this->assertFalse($this->url->parse('uploads/1/2/file.jpg'));
	}

	public function testWidth() {
		$this->assertEquals([
			'1/2/file.jpg', 200, null, []
		], $this->url->parse('uploads/1/2/file-200x_.jpg'));
	}

	public function testHeight() {
		$this->assertEquals([
			'1/2/file.jpg', null, 100, []
		], $this->url->parse('uploads/1/2/file-_x100.jpg'));
	}

	public function testWidthAndHeight() {
		$this->assertEquals([
			'1/2/file.jpg', 200, 100, []
		], $this->url->parse('uploads/1/2/file-200x100.jpg'));
	}

	public function testWidthAndHeightAndOptions() {
		$this->assertEquals([
			'1/2/file.jpg', 200, 100, ['resize' => null]
		], $this->url->parse('uploads/1/2/file-200x100-resize.jpg'));
	}

	public function testWidthAndHeightAndOptionsWithValue() {
		$this->assertEquals([
			'1/2/file.jpg', 200, 100, ['quadrant' => ['T']]
		], $this->url->parse('uploads/1/2/file-200x100-quadrant(T).jpg'));
	}

	public function testWidthAndHeightAndOptionsWithValueList() {
		$this->assertEquals([
			'1/2/file.jpg', 200, 100, ['trim_perc' => [0.25,0.25,0.75,0.75]]
		], $this->url->parse('uploads/1/2/file-200x100-trim_perc(0.25,0.25,0.75,0.75).jpg'));
	}

	public function testCropsInSubDirectory() {
		$url = new URL([
			'path' => 'images/(?:crops/)?(.*)$',
		]);
		$this->assertEquals([
			'file.jpg', 200, 100, []
		], $url->parse('images/crops/file-200x100.jpg'));
	}

}