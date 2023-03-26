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
        $cropPath = $this->render($requestPath);

        // Redirect to remote crops ...
        if ($this->storage->cropsAreRemote()) {
            return redirect(
                app('filesystem')
                    ->disk($this->config['crops_disk'])
                    ->url($cropPath),
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
     * Render image. Return the path to the crop relative to the storage disk.
     * @param string $requestPath
     * @return string|null
     * @throws Exception
     */
    public function render(string $requestPath): ?string
    {
        $params = ParameterBucket::createFrom($requestPath);
        $urlOptions = $params?->getUrlOptions() ?? [];
        $configOptions = $this->url->config($urlOptions);

        $cropPath = $this->getRelativeCropPath(
            $requestPath,
            $configOptions
        );

        if ($this->shouldReturnExistingCrop($cropPath)) {
            return $cropPath;
        }

        if (!$params) {
            return null;
        }

        $this->checkCropLimit($params->getPath());
        $this->increaseMemoryLimitIfNeeded();
        $image = $this->buildImage($params);
        $this->processAndWriteImage($image, $cropPath, $params);

        return $cropPath;
    }

    /**
     * Get crop path relative to its directory.
     * @throws Exception
     */
    protected function getRelativeCropPath(
        string $requestPath,
        array $options
    ): string
    {
        $relativePath = $this->url->relativePath($requestPath);

        $format = data_get($options, 'format');

        if ($format) {
            $relativePath = $this->replaceOriginalFileSuffix(
                $relativePath,
                $format
            );
        }

        return $relativePath;
    }

    protected function replaceOriginalFileSuffix(
        string $path,
        string $suffix
    ): string
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);
        $fileName = pathinfo($path, PATHINFO_FILENAME);

        return sprintf(
            '%s/%s.%s',
            $dirname,
            $fileName,
            $suffix
        );
    }

    /**
     * Determine if the existing crop should be returned.
     * @param string $cropPath
     * @return bool
     */
    protected function shouldReturnExistingCrop(string $cropPath): bool
    {
        return $this->storage->cropsAreRemote() &&
            $this->storage->cropExists($cropPath);
    }

    /**
     * Check if there are too many crops already.
     * @param string $path
     * @throws Exception
     */
    protected function checkCropLimit(string $path): void
    {
        if ($this->storage->tooManyCrops($path)) {
            throw new Exception('Croppa: Max crops');
        }
    }

    /**
     * Increase memory limit if needed.
     */
    protected function increaseMemoryLimitIfNeeded(): void
    {
        if ($this->config['memory_limit'] !== null) {
            ini_set('memory_limit', $this->config['memory_limit']);
        }
    }

    /**
     * Build a new image using fetched image data.
     */
    protected function buildImage(ParameterBucket $params): Image
    {
        return new Image(
            $this->storage->path($params->getPath()),
            $params->config()
        );
    }

    /**
     * Process the image and write its data to disk.
     * @throws Exception
     */
    protected function processAndWriteImage(
        Image $image,
        string $cropPath,
        ParameterBucket $params
    ): void
    {
        $newImage = $image->process(
            $params->getWidth(),
            $params->getHeight(),
            $params->getUrlOptions()
        );

        $this->storage->writeCrop(
            $cropPath,
            $newImage->get()
        );
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
