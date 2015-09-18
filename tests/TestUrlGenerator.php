<?php

use Bkwld\Croppa\URL;

class TestUrlGenerator extends PHPUnit_Framework_TestCase {

	public function testWidthAndHeight() {
		$url = new URL();
		$this->assertEquals('/path/file-200x100.png', $url->generate('/path/file.png', 200, 100));
	}

	public function testIgnore() {
		$url = new URL([ 'ignore' => '\.(?:gif|GIF)$' ]);
		$this->assertEquals('/path/file.gif', $url->generate('/path/file.gif', 200, 100));
		$this->assertEquals('/path/file-200x100.png', $url->generate('/path/file.png', 200, 100));
	}

	public function testNoWidthOrHeight() {
		$url = new URL();
		$this->assertEquals('/path/file.png', $url->generate('/path/file.png'));
	}

	public function testNoWidth() {
		$url = new URL();
		$this->assertEquals('/path/file-_x100.png', $url->generate('/path/file.png', null, 100));
	}

	public function testNoHeight() {
		$url = new URL();
		$this->assertEquals('/path/file-200x_.png', $url->generate('/path/file.png', 200));
	}

	public function testResize() {
		$url = new URL();
		$this->assertEquals('/path/file-200x100-resize.png', $url->generate('/path/file.png', 200, 100, ['resize']));
	}

	public function testQuadrant() {
		$url = new URL();
		$this->assertEquals('/path/file-200x100-quadrant(T).png', $url->generate('/path/file.png', 200, 100, ['quadrant' => 'T']));
	}

	public function testHostInSrc() {
		$url = new URL();
		$this->assertEquals('/path/file-200x_.png', $url->generate('http://domain.tld/path/file.png', 200));
		$this->assertEquals('/path/file-200x_.png', $url->generate('https://domain.tld/path/file.png', 200));
	}

	public function testHostConfig() {
		$url = new URL([ 'url_prefix' => '//domain.tld', ]);
		$this->assertEquals('//domain.tld/path/file-200x_.png', $url->generate('/path/file.png', 200));
	}

	public function testUrlPrefixWithSchema() {
		$url = new URL([ 'url_prefix' => 'https://domain.tld/' ]);
		$this->assertEquals('https://domain.tld/path/file-200x_.png', $url->generate('http://domain.tld/path/file.png', 200));
	}

	public function testUrlPrefixWithPath() {
		$url = new URL([
			'url_prefix' => 'https://domain.tld/path/',
			'path' => 'path/(.*)$',
		]);
		$this->assertEquals('https://domain.tld/path/file-200x_.png', $url->generate('/path/file.png', 200));
	}

	public function testCropsInSubDirectory() {
		$url = new URL([
			'path' => 'images/(?:crops/)?(.*)$',
			'url_prefix' => '/images/crops/',
		]);
		$this->assertEquals('/images/crops/file-200x100.png', $url->generate('/images/file.png', 200, 100));
	}

	public function testCropsInSiblingDirectory() {
		$url = new URL([
			'path' => 'images/(.*)$',
			'url_prefix' => '/crops/',
		]);
		$this->assertEquals('/crops/file-200x100.png', $url->generate('/images/file.png', 200, 100));
	}

	public function testSecure() {

		$url = new URL([ 'signing_key' => 'test' ]);
		$this->assertEquals('/path/file-200x100.png?token=dc0787d205f619a2b2df8554c960072e', $url->generate('/path/file.png', 200, 100));

		$url = new URL([ 'signing_key' => 'test' ]);
		$this->assertNotEquals('/path/file-200x100.png?token=dc0787d205f619a2b2df8554c960072e', $url->generate('/path/file.png', 200, 200));

	}

}
