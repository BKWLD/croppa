<?php

use Bkwld\Croppa\URL;

class TestUrlMatching extends PHPUnit_Framework_TestCase {

	private $url;
	public function setUp() {
		$this->url = new URL([
			'path' => 'uploads/(.*)$',
		]);
	}

	/**
	 * This mimics the Illuminate\Routing\Matching\UriValidator compiled regex
	 * https://regex101.com/r/xS3nQ2/1
	 */
	public function match($path) {

		// The compiled regex is wrapped like this
		$pattern = '#^\/(?P<path>'.$this->url->routePattern().')$#s';

		// UriValidator prepends a slash
		return preg_match($pattern, '/'.$path) > 0;
	}

	public function testNoParams() {
		$this->assertFalse($this->match('uploads/1/2/file.jpg'));
	}

	public function testOursideDir() {
		$this->assertFalse($this->match('assets/1/2/file.jpg'));
		$this->assertFalse($this->match('apple-touch-icon-152x152-precomposed.png'));
	}

	public function testWidth() {
		$this->assertTrue($this->match('uploads/1/2/file-200x_.jpg'));
	}

	public function testHeight() {
		$this->assertTrue($this->match('uploads/1/2/file-_x100.jpg'));
	}

	public function testWidthAndHeight() {
		$this->assertTrue($this->match('uploads/1/2/file-200x100.jpg'));
	}

	public function testWidthAndHeightAndOptions() {
		$this->assertTrue($this->match('uploads/1/2/file-200x100-quadrant(T).jpg'));
	}

}