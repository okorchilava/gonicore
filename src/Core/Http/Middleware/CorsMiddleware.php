<?php

declare(strict_types=1);

namespace GoniCore\Core\Http\Middleware;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

/**
 * Cross-Origin Resource Sharing (CORS) middleware.
 *
 * Handles preflight OPTIONS requests and injects CORS headers
 * on every response. Configure allowed origins via the constructor
 * or bind a custom instance in the DI container.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $allowOrigin  = '*',
        private readonly string $allowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        private readonly string $allowHeaders = 'Content-Type, Authorization, X-Requested-With',
        private readonly int    $maxAge       = 86400,
    ) {}

    public function process(Request $request, callable $next): Response
    {
        // Short-circuit preflight requests immediately.
        if ($request->isMethod('OPTIONS')) {
            return Response::json(null, 204)
                ->withHeader('Access-Control-Allow-Origin',  $this->allowOrigin)
                ->withHeader('Access-Control-Allow-Methods', $this->allowMethods)
                ->withHeader('Access-Control-Allow-Headers', $this->allowHeaders)
                ->withHeader('Access-Control-Max-Age',       (string) $this->maxAge);
        }

        /** @var Response $response */
        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin',  $this->allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', $this->allowMethods)
            ->withHeader('Access-Control-Allow-Headers', $this->allowHeaders);
    }
}
