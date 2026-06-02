<?php

declare(strict_types=1);

namespace GoniCore\Core\Http\Middleware;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

/**
 * Builds and executes an ordered middleware pipeline.
 *
 * Middleware is applied in registration order (FIFO): the first pipe()
 * call wraps the outermost layer, the last wraps the innermost.
 *
 * Usage:
 *   $response = (new MiddlewarePipeline())
 *       ->pipe(new CorsMiddleware())
 *       ->pipe(new AuthMiddleware())
 *       ->run($request, fn(Request $r): Response => $handler($r));
 */
final class MiddlewarePipeline
{
    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    /**
     * Add a middleware layer. Returns a new instance (immutable).
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $clone             = clone $this;
        $clone->middleware[] = $middleware;
        return $clone;
    }

    /**
     * Execute the pipeline and return the final Response.
     *
     * @param callable(Request): Response $handler  The core handler at the end of the chain.
     */
    public function run(Request $request, callable $handler): Response
    {
        // Build the chain from the inside out using array_reduce so the
        // first registered middleware is the outermost (executes first).
        $chain = array_reduce(
            array_reverse($this->middleware),
            static function (callable $carry, MiddlewareInterface $mw): callable {
                return static fn(Request $req): Response => $mw->process($req, $carry);
            },
            $handler,
        );

        return $chain($request);
    }
}
