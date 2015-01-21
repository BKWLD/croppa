# Croppa

Croppa is an thumbnail generator bundle for Laravel 4.x.  It follows a different approach from libraries that store your thumbnail dimensions in the model, like [Paperclip](https://github.com/thoughtbot/paperclip).  Instead, the resizing and cropping instructions come from specially formatted urls.  For instance, say you have an image with this path:

    /uploads/09/03/screenshot.png

To produce a 300x200 thumbnail of this, you would change the path to:

    /uploads/09/03/screenshot-300x200.png

This file, of course, doesn't exist yet.  Croppa listens for specifically formatted image routes and build this thumbnail on the fly, outputting the image data (with correct headers) to the browser instead of the 404 response.

At the same time, it saves the newly cropped image to the disk in the same location (the "…-300x200.png" path) that you requested.  As a result, **all future requests get served directly from the disk**, bybassing PHP and all that overhead.  This is a differentiating point compared to other, similar libraries.


## Installation [![Build Status](https://travis-ci.org/BKWLD/croppa.svg?branch=master)](https://travis-ci.org/BKWLD/croppa)

#### Server Requirements:

* [gd](http://php.net/manual/en/book.image.php)
* [exif](http://php.net/manual/en/book.exif.php) - Required if you want to have Croppa auto-rotate images from devices like mobile phones based on exif meta data.

#### Installation: 

1. Add Croppa to your composer.json's requires: `"bkwld/croppa": "~3.0"`.  Then do a regular composer install.
2. Add Croppa as a provider in your app/config/app.php's provider list: `'Bkwld\Croppa\ServiceProvider',`
3. Add the facade to your app/config/app.php's aliases: `'Croppa' => 'Bkwld\Croppa\Facade'`,

## Configuration

* **src_dirs**: An array of absolute paths where your relative image paths are searched for.  The first match is used.  By default, Croppa looks in /public/, expecting you to upload your images to a directory like /public/uploads and storing the relative path of "/uploads/path/to/file.png" in your database.
* **max_crops** (12): An optional number that limits how many crops you allow Croppa to create per source image.
* **jpeg_quality** (95): An integer from 0-100 for the quality of generated jpgs.
* **interlace** (true): This boolean affects whether progressive jpgs are created.
* **host** (undefined): Specify the host for Croppa::url() to use when generating absolute paths to images.  If undefined and using Laravel, the `Request::host()` is used by default.
* **public** (undefined): Specify the route to the document_root of your app.  If undefined and using Laravel, the `public_path()` is used by default.
* **ignore** (undefined): Ignore cropping for image URLs that match a regular expression. Useful for returning animated gifs.

Note: Croppa will attempt to create the crops in the same directory as the source image.  Thus, this directory **must be made writeable**.

## Usage

The URL schema that Croppa uses is:

    /path/to/image-widthxheight-option1-option2(arg1,arg2).ext

So these are all valid:

    /uploads/image-300x200.png             // Crop to fit in 300x200
    /uploads/image-_x200.png               // Resize to height of 200px
    /uploads/image-300x_.png               // Resize to width of 300px
    /uploads/image-300x200-resize.png      // Resize to fit within 300x200
    /uploads/image-300x200-quadrant(T).png // See the quadrant description below

### Croppa::url($src, $width, $height, array($options))

To make preparing the URLs that Croppa expects an easier job, you can use the following view helper:

```php
<img src="<?=Croppa::url($path, $width, $height, $options)?>" />
<!-- Examples (that would produce the URLs above) -->
<img src="<?=Croppa::url('/uploads/image.png', 300, 200)?>" />
<img src="<?=Croppa::url('/uploads/image.png', null, 200)?>" />
<img src="<?=Croppa::url('/uploads/image.png', 300)?>" />
<img src="<?=Croppa::url('/uploads/image.png', 300, 200, array('resize'))?>" />
<img src="<?=Croppa::url('/uploads/image.png', 300, 200, array('quadrant' => 'T'))?>" />
<!-- Or, if there were multiple arguments for the last example -->
<img src="<?=Croppa::url('/uploads/image.png', 300, 200, array('quadrant' => array('T')))?>" />
```

These are the arguments that Croppa::url() takes:

* $src : The relative path to your image.  It is relative to a directory that you specified in the config's **src_dirs**
* $width : A number or null for wildcard
* $height : A number or null for wildcard
* $options - An array of key value pairs, where the value is an optional array of arguments for the option.  Supported option are:
  * `resize` - Make the image fit in the provided width and height through resizing.  When omitted, the default is to crop to fit in the bounds (unless one of sides is a wildcard).
  * `quadrant($quadrant)` - Crop the remaining overflow of an image using the passed quadrant heading.  The supported `$quadrant` values are: `T` - Top (good for headshots), `B` - Bottom, `L` - Left, `R` - Right, `C` - Center (default).  See the [PHPThumb documentation](https://github.com/masterexploder/PHPThumb/blob/master/src/PHPThumb/GD.php#L485) for more info.
  * `trim($x1, $y1, $x2, $y2)` - Crop the source image to the size defined by the two sets of coordinates ($x1, $y1, ...) BEFORE applying the $width and $height parameters.  This is designed to be used with a frontend cropping UI like [jcrop](http://deepliquid.com/content/Jcrop.html) so that you can respect a cropping selection that the user has defined but then output thumbnails or sized down versions of that selection with Croppa.
  * `trim_perc($x1_perc, $y1_perc, $x2_perc, $y2_perc)` - Has the same effect as `trim()` but accepts coordinates as percentages.  Thus, the the upper left of the image is "0" and the bottom right of the image is "1".  So if you wanted to trim the image to half the size around the center, you would add an option of `trim(0.25,0.25,0.75,0.75)`
  * `quality($int)` - Set the jpeg compression quality from 0 to 100.
  * `interlace($bool)` - Set to `1` or `0` to turn interlacing on or off

 Note: Croppa will not upscale images.  In other words, if you ask for a size bigger than the source, it will **only** create an image as big as the original source (though possibly cropped to give you the aspect ratio you requested).

### Croppa::delete($src)

You can delete a source image and all of it's crops (like if a related DB row was deleted) by running:

```php
Croppa::delete('/path/to/src.png');
```

### Croppa::sizes($src, $width, $height, array($options))


You can get the width and height of the image for putting in a style tag by passing the same args as `Croppa::url()` expexts to `Croppa::sizes()`:

```php
<img src="…" style="<?=Croppa::sizes('/uploads/image.png', 300)?>" />
```


## croppa.js

A module is included to prepare formatted URLs from JS.  This can be helpful when you are creating views from JSON responses from an AJAX request; you don't need to format the URLs on the server.  It can be loaded via Require.js, CJS, or as browser global variable.

### croppa.url(src, width, height, options)

Works just like the PHP `Croppa::url` except for how options get formatted (since JS doesn't have associative arrays).

```js
croppa.url('/path/to/img.jpg', 300, 200, ['resize']);
croppa.url('/path/to/img.jpg', 300, 200, ['resize', {quadrant: 'T'}]);
croppa.url('/path/to/img.jpg', 300, 200, ['resize', {quadrant: ['T']}]);
```

Run `php artisan bundle:publish croppa` to have Laravel copy the JS to your public directory.  It will go to /public/bundles/croppa/js by default.


## Thanks

This bundle uses [PHPThumb](https://github.com/masterexploder/PHPThumb) to do all the [image resizing](https://github.com/masterexploder/PHPThumb/wiki/Basic-Usage).  "Crop" is equivalent to it's adaptiveResize() and "resize" is … resize().  
