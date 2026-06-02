<?php

declare(strict_types=1);

namespace GoniCore\Core\Http\Middleware;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

/**
 * Contract for all HTTP middleware in GoniCore.
 *
 * A middleware wraps a request/response cycle.
 * Call $next($request) to forward to the next layer.
 *
 * Example:
 *   final class AuthMiddleware implements MiddlewareInterface
 *   {
 *       public function process(Request $request, callable $next): Response
 *       {
 *           if (!$this->isAuthenticated($request)) {
 *               return Response::unauthorized();
 *           }
 *           return $next($request);
 *       }
 *   }
 */
interface MiddlewareInterface
{
    /**
     * @param callable(Request): Response $next  The next handler in the pipeline.
     */
    public function process(Request $request, callable $next): Response;
}
