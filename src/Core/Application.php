<?php

declare(strict_types=1);

namespace GoniCore\Core;

use GoniCore\Core\Config\Config;
use GoniCore\Core\Container\Container;
use GoniCore\Core\Http\HttpException;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Http\Router;
use GoniCore\Modules\Theme\ThemeController;
use Throwable;

/**
 * Application kernel — ties together the container, router, and config.
 *
 * Typical entry-point usage (public/index.php):
 *
 *   $app = require __DIR__ . '/../bootstrap/app.php';
 *   $app->run();
 */
final class Application
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly Config $config,
    ) {}

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    /**
     * Handle the current HTTP request and send the response to the client.
     * This method does NOT return — it terminates the process via exit().
     */
    public function run(): never
    {
        $response = $this->handle(Request::capture());
        $response->send();
        exit(0);
    }

    /**
     * Handle a Request and return a Response without any side effects.
     * Useful for testing — does not echo anything or call exit().
     */
    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (HttpException $e) {
            // API requests always get JSON errors.
            // Web requests get an HTML 404/error page where possible.
            if ($e->getStatusCode() === 404 && !$this->isApiRequest($request)) {
                try {
                    return $this->container->get(ThemeController::class)->notFound($request);
                } catch (Throwable) {
                    // Theme unavailable — fall through to JSON.
                }
            }

            return Response::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrors(),
            );
        } catch (Throwable $e) {
            return $this->handleUnexpected($e);
        }
    }

    /**
     * Returns true for requests whose path starts with /api/.
     */
    private function isApiRequest(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');
        // Strip basePath prefix to get the route path
        $base = $request->basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        return str_starts_with($path, '/api/');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function container(): Container
    {
        return $this->container;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(): Config
    {
        return $this->config;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    private function handleUnexpected(Throwable $e): Response
    {
        $debug = (bool) $this->config->get('app.debug', false);

        if ($debug) {
            return Response::error(
                $e->getMessage(),
                500,
                ['exception' => $e::class, 'file' => $e->getFile(), 'line' => $e->getLine()],
            );
        }

        // In production, log the real error and return a generic 500.
        error_log(sprintf(
            '[GoniCore] Uncaught %s: %s in %s on line %d',
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
        ));

        return Response::error('Internal Server Error', 500);
    }
}
