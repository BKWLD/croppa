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
		$url = new URL([ 'url_prefix' => 'https://domain.tld/path/', 'path' => 'path/(.*)$' ]);
		$this->assertEquals('https://domain.tld/path/file-200x_.png', $url->generate('/path/file.png', 200));
	}

}