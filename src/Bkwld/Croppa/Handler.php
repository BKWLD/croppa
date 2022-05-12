<?php

namespace Bkwld\Croppa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handle a Croppa-style request, forwarding the actual work onto other classes.
 */
class Handler extends Controller
{
    /**
     * @var Bkwld\Croppa\Storage
     */
    private $storage;

    /**
     * @var Bkwld\Croppa\URL
     */
    private $url;

    /**
     * @var array
     */
    private $config;

    /**
     * Dependency injection.
     *
     * @param Bkwld\Croppa\URL        $url
     * @param Bkwld\Croppa\Storage    $storage
     * @param Illuminate\Http\Request $request
     * @param array                   $config
     */
    public function __construct(URL $url, Storage $storage, Request $request, $config = null)
    {
        $this->url = $url;
        $this->storage = $storage;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * Handles a Croppa style route.
     *
     * @throws Exception
     *
     * @return Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function handle(string $requestPath)
    {
        // Validate the signing token
        $token = $this->url->signingToken($requestPath);
        if ($token !== $this->request->input('token')) {
            throw new NotFoundHttpException('Token mismatch');
        }

        // Create the image file
        $cropPath = $this->render($requestPath);

        // Redirect to remote crops ...
        if ($this->storage->cropsAreRemote()) {
            return new RedirectResponse($this->url->pathToUrl($cropPath), 301);
            // ... or echo the image data to the browser
        }
        $absolutePath = $this->storage->getLocalCropsDirPath().'/'.$cropPath;

        return new BinaryFileResponse($absolutePath, 200, [
            'Content-Type' => $this->getContentType($absolutePath),
        ]);
    }

    /**
     * Render image directly.
     *
     * @return string The path, relative to the storage disk, to the crop
     */
    public function render(string $requestPath)
    {
        // Get crop path relative to it's dir
        $cropPath = $this->url->relativePath($requestPath);

        // If the crops_dir is a remote disk and if the crop has already been
        // created.  If it has, just return that path.
        if ($this->storage->cropsAreRemote()
            && $this->storage->cropExists($cropPath)) {
            return $cropPath;
        }

        // Parse the path.  In the case there is an error (the pattern on the route
        // SHOULD have caught all errors with the pattern) just return
        if (!$params = $this->url->parse($requestPath)) {
            return;
        }
        list($path, $width, $height, $options) = $params;

        // Check if there are too many crops already
        if ($this->storage->tooManyCrops($path)) {
            throw new Exception('Croppa: Max crops');
        }

        // Increase memory limit, cause some images require a lot to resize
        if ($this->config['memory_limit'] !== null) {
            ini_set('memory_limit', $this->config['memory_limit']);
        }

        // Build a new image using fetched image data
        $image = new Image(
            $this->storage->readSrc($path),
            $this->url->phpThumbConfig($options)
        );

        // Process the image and write its data to disk
        $this->storage->writeCrop(
            $cropPath,
            $image->process($width, $height, $options)->get()
        );

        // Return the paht to the crop, relative to the storage disk
        return $cropPath;
    }

    /**
     * Symfony kept returning the MIME-type of my testing jpgs as PNGs, so
     * determining it explicitly via looking at the path name.
     *
     * @param string $path
     *
     * @return string
     */
    public function getContentType($path)
    {
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';

            case 'gif':
                return 'image/gif';

            case 'png':
                return 'image/png';
        }
    }
}
