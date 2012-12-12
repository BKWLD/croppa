# Croppa

Croppa is an thumbnail generator bundle for Laravel.  It follows a different approach from libraries that store your thumbnail dimensions in the model, like [Paperclip](https://github.com/thoughtbot/paperclip).  Instead, the resizing and cropping instructions come from specially formatted urls.  For instance, say you have an image with this path:

    /uploads/09/03/screenshot.png

To produce a 300x200 thumbnail of this, you would change the path to:

    /uploads/09/03/screenshot-300x200.png

This file, of course, doesn't exist yet.  Croppa listens for the 404 event and build this thumbnail on the fly, outputting the image data (with correct headers) to the browser instead of the 404 response.

At the same time, it saves the newly cropped image to the disk in the same location (the "…-300x200.png" path) that you requested.  As a result, **all future requests get served directly from the disk**, bybassing PHP and all that overhead.  This is a differentiating point compared to other, similar libraries.

## Installation

1. Install it with `php artisan bundle:install croppa`
2. Register the bundle in application/bundles.php with: `return array('croppa' => array('auto' => true))`

## Configuration

* **src_dirs**: An array of absolute paths where your relative image paths are searched for.  The first match is used.  By default, Croppa looks in /public/, expecting you to upload your images to a directory like /public/uploads and storing the relative path of "/uploads/path/to/file.png" in your database.
* **max_crops**: An optional number that limits how many crops you allow Croppa to create per source image.

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

To make preparing the URLs that Croppa expects an easier job, you can use the following view helper:

```php
<img src="<?=Croppa::url($path, $width, $height, $options)?>" />
<!-- Examples (that would produce the URLs above) -->
<img src="<?=Croppa::url('/uploads/image.png', 300, 200)?>" />
<img src="<?=Croppa::url('/uploads/image.png', null, 200)?>" />
<img src="<?=Croppa::url('/uploads/image.png', 300)?>" />
<img src="<?=Croppa::url('/uploads/image.png', 300, 200, array('resize'))?>" />
<img src="<?=Croppa::url('/uploads/image.png', 300, 200, array('quadrant' => array('T')))?>" />
```

These are the arguments that Croppa::url() takes:

* $path : The relative path to your image.  It is relative to a directory that you specified in the config's **src_dirs**
* $width : A number or null for wildcard
* $height : A number or null for wildcard
* $options - An array of key value pairs, where the value is an optional array of arguments for the opiton.  Supported option are:
  * `resize` - Make the image fit in the provided width and height through resizing.  When omitted, the default is to crop to fit in the bounds (unless one of sides is a wildcard).
  * `quadrant($quadrant)` - Crop the remaining overflow of an image using the passed quadrant heading.  The supported `$quadrant` values are: `T` - Top (good for headshots), `B` - Bottom, `L` - Left, `R` - Right, `C` - Center (default).  See the [PHPThumb documentation](https://github.com/masterexploder/PHPThumb/blob/master/src/GdThumb.inc.php#L534) for more info.

You can delete a source image and all of it's crops (like if a related DB row was deleted) by running:

```php
Croppa::delete('/path/to/src.png');
```

## Thanks

This bundle uses [PHPThumb](https://github.com/masterexploder/PHPThumb) to do all the [image resizing](https://github.com/masterexploder/PHPThumb/wiki/Basic-Usage).  "Crop" is equivalent to it's adaptiveResize() and "resize" is … resize().  