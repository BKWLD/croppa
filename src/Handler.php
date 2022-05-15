<?php

namespace Bkwld\Croppa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handle a Croppa-style request, forwarding the actual work onto other classes.
 */
class Handler extends Controller
{
    /**
     * @var URL
     */
    private $url;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $config;

    /**
     * Dependency injection.
     */
    public function __construct(URL $url, Storage $storage, Request $request, ?array $config = null)
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
     */
    public function handle(string $requestPath): mixed
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
            return redirect(app('filesystem')->disk($this->config['crops_disk'])->url($cropPath), 301);
            // ... or echo the image data to the browser
        }
        $absolutePath = $this->storage->getLocalCropPath($cropPath);

        return new BinaryFileResponse($absolutePath, 200, [
            'Content-Type' => $this->getContentType($absolutePath),
        ]);
    }

    /**
     * Render image. Return the path to the crop relative to the storage disk.
     */
    public function render(string $requestPath): ?string
    {
        // Get crop path relative to it’s dir
        $cropPath = $this->url->relativePath($requestPath);

        // If the crops_disk is a remote disk and if the crop has already been
        // created. If it has, just return that path.
        if ($this->storage->cropsAreRemote() && $this->storage->cropExists($cropPath)) {
            return $cropPath;
        }

        // Parse the path. In the case there is an error (the pattern on the route
        // SHOULD have caught all errors with the pattern), return null.
        if (!$params = $this->url->parse($requestPath)) {
            return null;
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
            $this->storage->path($path),
            $this->url->config($options)
        );

        // Process the image and write its data to disk
        $this->storage->writeCrop(
            $cropPath,
            $image->process($width, $height, $options)->get()
        );

        // Return the path to the crop, relative to the storage disk
        return $cropPath;
    }

    /**
     * Determining MIME-type via the path name.
     */
    public function getContentType(string $path): string
    {
        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'gif':
                return 'image/gif';

            case 'png':
                return 'image/png';

            case 'webp':
                return 'image/webp';

            default:
                return 'image/jpeg';
        }
    }
}
