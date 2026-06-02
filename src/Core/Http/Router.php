<?php

declare(strict_types=1);

namespace GoniCore\Core\Http;

use GoniCore\Core\Container\Container;
use GoniCore\Core\Http\HttpException;
use GoniCore\Core\Http\Middleware\MiddlewarePipeline;
use RuntimeException;

/**
 * HTTP router — registers routes and dispatches incoming requests.
 *
 * Route registration:
 *   $router->get('/posts',         [PostController::class, 'index']);
 *   $router->post('/posts',        [PostController::class, 'store']);
 *   $router->get('/posts/{id}',    [PostController::class, 'show'])->name('posts.show');
 *   $router->delete('/posts/{id}', fn(Request $r) => Response::json([], 204));
 *
 * Group (shared prefix):
 *   $router->group('/api/v1', function (Router $r) {
 *       $r->get('/health', fn() => Response::json(['ok' => true]));
 *   });
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    private string $groupPrefix = '';

    public function __construct(private readonly ?Container $container = null) {}

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    public function get(string $path, mixed $handler): Route
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): Route
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): Route
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): Route
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): Route
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function options(string $path, mixed $handler): Route
    {
        return $this->addRoute('OPTIONS', $path, $handler);
    }

    /**
     * Group routes under a shared URI prefix.
     * Groups can be nested.
     */
    public function group(string $prefix, callable $callback): void
    {
        $previous          = $this->groupPrefix;
        $this->groupPrefix = $previous . $prefix;

        $callback($this);

        $this->groupPrefix = $previous;
    }

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    /**
     * Match the request to a registered route and return the Response.
     * Returns a 404 JSON error if no route matches.
     * Returns a 405 JSON error if the path matched but the method did not.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        $methodNotAllowed = false;

        foreach ($this->routes as $route) {
            // Check path first (to detect 405 vs 404).
            if (preg_match($route->toRegex(), $path)) {
                $params = $route->match($method, $path);

                if ($params === null) {
                    // Path matches but method does not.
                    $methodNotAllowed = true;
                    continue;
                }

                // Inject route parameters as request attributes.
                foreach ($params as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }

                return $this->runRoute($route, $request);
            }
        }

        if ($methodNotAllowed) {
            throw new HttpException(405, "Method Not Allowed: {$method}");
        }

        throw new HttpException(404, "Route not found: {$method} {$path}");
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    private function addRoute(string $method, string $path, mixed $handler): Route
    {
        $route          = new Route($method, $this->groupPrefix . $path, $handler);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Execute the route's middleware pipeline, then call the handler.
     */
    private function runRoute(Route $route, Request $request): Response
    {
        $handler    = $route->getHandler();
        $middleware = $route->getMiddleware();

        $core = function (Request $req) use ($handler): Response {
            if (is_callable($handler)) {
                return $handler($req);
            }

            if (is_array($handler) && count($handler) === 2) {
                [$class, $method] = $handler;

                $controller = $this->container !== null
                    ? $this->container->get($class)
                    : new $class();

                return $controller->{$method}($req);
            }

            throw new RuntimeException('Invalid route handler: must be callable or [Class, method].');
        };

        if (empty($middleware)) {
            return $core($request);
        }

        $pipeline = new MiddlewarePipeline();

        foreach ($middleware as $mw) {
            $pipeline = $pipeline->pipe($mw);
        }

        return $pipeline->run($request, $core);
    }
}
