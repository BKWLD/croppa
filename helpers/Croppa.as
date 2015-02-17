package com.bkwld {

	/**
	 * An ActionScript port of https://raw.github.com/BKWLD/croppa/master/public/js/croppa.js
	 */
	public class Croppa {

		/**
		 * Build a croppa formatted URL
		 * @param  src     Absolute path to the source image
		 * @param  width   Width to crop to.  Set to -1, 0, or NaN to make it wildcarded
		 * @param  height  Height to crop to.  Set to -1, 0, or NaN to make it wildcarded
		 * @param  options Addtional Croppa options, passed as key/value pairs.  Like ['resize']
		 * @return A Croppa-friendly URL
		 */
		static public function url(src:String, width:Number = NaN, height:Object = NaN, options:Object = null):String {

			// Produce the croppa syntax
			var suffix:String = '-';
			if (width > 0 && (!height || height <= 0)) suffix += String(width)+'x_';
			else if ((!width || width <= 0) && height > 0) suffix += '_x'+String(height);
			else if (width > 0 && height > 0) suffix += String(width)+'x'+String(height);
			else suffix += '_x_';

			// Disable options for now
			if (options) throw new Error('Croppa.url options not supported yet');

			// Break the path apart and put back together again
			return src.replace(/^(.+)(\.[a-z]+)$/i, "$1"+suffix+"$2");

		}

	}
}
