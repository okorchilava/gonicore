<?php

declare(strict_types=1);

namespace GoniCore\Core\Http;

use GoniCore\Core\Http\Middleware\MiddlewareInterface;

/**
 * Represents a single registered route.
 *
 * Supports:
 *   - Named parameters:  /posts/{id}  /users/{userId}/posts/{postId}
 *   - Optional name:     ->name('posts.show')
 *   - Per-route middleware applied in registration order.
 */
final class Route
{
    private ?string $name = null;

    /** @var list<MiddlewareInterface> */
    private array $middleware = [];

    /**
     * @param string                   $method   Uppercase HTTP verb.
     * @param string                   $path     URI pattern, e.g. /posts/{id}.
     * @param callable|array{0:string,1:string} $handler  Closure or [ControllerClass, method].
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly mixed $handler,
    ) {}

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(MiddlewareInterface ...$middleware): self
    {
        array_push($this->middleware, ...$middleware);
        return $this;
    }

    // -------------------------------------------------------------------------
    // Matching
    // -------------------------------------------------------------------------

    /**
     * Attempt to match an HTTP method + URI path against this route.
     *
     * @return array<string, string>|null  Named captures on match, null on miss.
     */
    public function match(string $method, string $path): ?array
    {
        if (strtoupper($method) !== $this->method) {
            return null;
        }

        if (!preg_match($this->toRegex(), $path, $matches)) {
            return null;
        }

        // Return only the named captures (string keys).
        return array_filter(
            $matches,
            static fn(mixed $key): bool => is_string($key),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Compile the path pattern to a PCRE regex.
     *
     * /posts/{id}  →  #^/posts/(?P<id>[^/]+)$#
     */
    public function toRegex(): string
    {
        $escaped = preg_quote($this->path, '#');

        // Un-escape the braces so we can replace {param} placeholders.
        $pattern = (string) preg_replace(
            '/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/',
            '(?P<$1>[^/]+)',
            $escaped,
        );

        return '#^' . $pattern . '$#';
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @return list<MiddlewareInterface> */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}
