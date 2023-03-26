<?php

namespace Bkwld\Croppa;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
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
    protected Renderer $renderer;

    /**
     * Dependency injection.
     */
    public function __construct(
        URL $url,
        Storage $storage,
        Request $request,
        ?array $config = null
    )
    {
        $this->url = $url;
        $this->storage = $storage;
        $this->request = $request;
        $this->config = $config;
        $this->renderer = new Renderer($url, $storage, $config);
    }

    /**
     * Handles a Croppa style route.
     *
     * @param string $requestPath
     * @return BinaryFileResponse|Application|Redirector|RedirectResponse
     * @throws Exception
     */
    public function handle(string $requestPath):
    BinaryFileResponse|Application|Redirector|RedirectResponse
    {
        // Validate the signing token
        $token = $this->url->signingToken($requestPath);
        if ($token !== $this->request->input('token')) {
            throw new NotFoundHttpException('Token mismatch');
        }

        // Create the image file
        $cropPath = $this->renderer->render($requestPath);

        // Redirect to remote crops ...
        if ($this->storage->cropsAreRemote()) {
            $cropsDisk = app('filesystem')
                ->disk($this->config['crops_disk']);
            return redirect(
                $cropsDisk->url($cropPath),
                301
            );
            // ... or echo the image data to the browser
        }
        $absolutePath = $this->storage->getLocalCropPath($cropPath);

        return new BinaryFileResponse($absolutePath, 200, [
            'Content-Type' => $this->getContentType($absolutePath),
        ]);
    }

    /**
     * Determining MIME-type via the path name.
     */
    public function getContentType(string $path): string
    {
        return match (pathinfo($path, PATHINFO_EXTENSION)) {
            'gif' => 'image/gif',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
