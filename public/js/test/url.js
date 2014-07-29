// These tests were designed to be run via Mocha

var assert = require("assert"),
	croppa = require('../croppa');

describe('Croppa', function() {
	describe('#url', function() {
	
		it('should append width and height when passed', function(){
			assert.equal('/path/to/file-200x100.jpg', croppa.url('/path/to/file.jpg', 200, 100));
			assert.equal('/path/to/file-200x_.jpg', croppa.url('/path/to/file.jpg', 200));
			assert.equal('/path/to/file-_x100.jpg', croppa.url('/path/to/file.jpg', null, 100));
		});

		it('should allow width or height to be empty', function(){
			assert.equal('/path/to/file-200x_.jpg', croppa.url('/path/to/file.jpg', 200));
			assert.equal('/path/to/file-_x100.jpg', croppa.url('/path/to/file.jpg', null, 100));
		});

		it('should allow the setting of string options', function(){
			assert.equal('/path/to/file-200x100-resize.jpg', croppa.url('/path/to/file.jpg', 200, 100, ['resize']));
		});

		it('should allow the setting of key value options', function(){
			assert.equal('/path/to/file-200x100-quadrant(T).jpg', croppa.url('/path/to/file.jpg', 200, 100, [{quadrant: 'T'}]));
			assert.equal('/path/to/file-200x100-quadrant(T).jpg', croppa.url('/path/to/file.jpg', 200, 100, [{quadrant: ['T']}]));
			assert.equal('/path/to/file-200x100-coordinates(1,2,3,4).jpg', croppa.url('/path/to/file.jpg', 200, 100, [{coordinates: [1,2,3,4]}]));
		});

		it('should allow the setting of multiple options', function(){
			assert.equal('/path/to/file-200x100-resize-quadrant(T).jpg', croppa.url('/path/to/file.jpg', 200, 100, ['resize', {quadrant: 'T'}]));
		});
	
	});
});