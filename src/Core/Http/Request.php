<?php

declare(strict_types=1);

namespace GoniCore\Core\Http;

/**
 * Immutable HTTP request value object.
 *
 * Captures the current PHP superglobals via Request::capture().
 * Attributes (route parameters, middleware-injected values) are stored
 * on a cloned instance so the original is never mutated.
 */
final class Request
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    /** Lazily decoded JSON body — null means "not yet parsed". */
    private ?array $parsedJson = null;

    /**
     * @param array<string, mixed> $query    $_GET
     * @param array<string, mixed> $post     $_POST
     * @param array<string, mixed> $server   $_SERVER
     * @param array<string, mixed> $cookies  $_COOKIE
     * @param array<string, mixed> $files    $_FILES
     */
    private function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query,
        private readonly array $post,
        private readonly array $server,
        private readonly array $cookies,
        private readonly array $files,
        private readonly string $rawBody,
    ) {}

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Build a Request from PHP's superglobals and php://input.
     */
    public static function capture(): self
    {
        return new self(
            method:  strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            uri:     $_SERVER['REQUEST_URI'] ?? '/',
            query:   $_GET,
            post:    $_POST,
            server:  $_SERVER,
            cookies: $_COOKIE,
            files:   $_FILES,
            rawBody: (string) file_get_contents('php://input'),
        );
    }

    // -------------------------------------------------------------------------
    // Request metadata
    // -------------------------------------------------------------------------

    public function method(): string
    {
        return $this->method;
    }

    /** Full URI including query string, e.g. /posts?page=2 */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * URI path relative to the application root, without query string.
     *
     * Handles two deployment scenarios automatically:
     *
     *   A) Direct access to public/:
     *      REQUEST_URI  = /goni/GoniCore/public/api/v1/health
     *      SCRIPT_NAME  = /goni/GoniCore/public/index.php
     *      → strips /goni/GoniCore/public  → /api/v1/health
     *
     *   B) Root .htaccess rewrite (REQUEST_URI has no /public/ prefix):
     *      REQUEST_URI  = /goni/GoniCore/api/v1/health
     *      SCRIPT_NAME  = /goni/GoniCore/public/index.php
     *      → strips /goni/GoniCore         → /api/v1/health
     *
     *   C) Virtual host (doc root = public/):
     *      REQUEST_URI  = /api/v1/health
     *      SCRIPT_NAME  = /index.php
     *      → nothing to strip              → /api/v1/health
     */
    public function path(): string
    {
        $uri    = (string) (parse_url($this->uri, PHP_URL_PATH) ?? '/');
        $script = (string) ($this->server['SCRIPT_NAME'] ?? '');

        // Level 1 — public/ base  (dirname of SCRIPT_NAME)
        $publicBase = rtrim(dirname($script), '/\\');

        if ($publicBase !== '' && $publicBase !== '.') {
            if (str_starts_with($uri, $publicBase . '/') || $uri === $publicBase) {
                $stripped = substr($uri, strlen($publicBase));
                return $stripped !== '' ? $stripped : '/';
            }

            // Level 2 — project root  (dirname of public/ base)
            // Triggered when root .htaccess rewrites WITHOUT the /public/ segment.
            $projectBase = rtrim(dirname($publicBase), '/\\');

            if ($projectBase !== '' && $projectBase !== '.') {
                if (str_starts_with($uri, $projectBase . '/') || $uri === $projectBase) {
                    $stripped = substr($uri, strlen($projectBase));
                    return $stripped !== '' ? $stripped : '/';
                }
            }
        }

        return $uri !== '' ? $uri : '/';
    }

    /**
     * Return the URL prefix that was stripped by path().
     *
     * Useful for generating correct hrefs in themes when the app
     * lives in a subdirectory (e.g. /goni/GoniCore).
     *
     * Examples:
     *   Virtual host  →  ''
     *   Subdirectory  →  '/goni/GoniCore'
     */
    public function basePath(): string
    {
        $rawPath = (string) (parse_url($this->uri, PHP_URL_PATH) ?? '/');
        $appPath = $this->path();

        if ($rawPath === $appPath) {
            return '';
        }

        if ($appPath === '/') {
            return rtrim($rawPath, '/');
        }

        $pos = strrpos($rawPath, $appPath);
        if ($pos !== false) {
            return substr($rawPath, 0, $pos);
        }

        return '';
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    // -------------------------------------------------------------------------
    // Input
    // -------------------------------------------------------------------------

    /**
     * Query-string value ($_GET).
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * POST body value ($_POST).
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Read from query string → POST body → JSON body, in that order.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->query[$key]
            ?? $this->post[$key]
            ?? $this->json()[$key]
            ?? $default;
    }

    /**
     * Return the decoded JSON request body as an associative array.
     * Returns [] if the body is empty or the Content-Type is not JSON.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if ($this->parsedJson !== null) {
            return $this->parsedJson;
        }

        if ($this->isJson() && $this->rawBody !== '') {
            $decoded          = json_decode($this->rawBody, true);
            $this->parsedJson = is_array($decoded) ? $decoded : [];
        } else {
            $this->parsedJson = [];
        }

        return $this->parsedJson;
    }

    /** Raw request body string. */
    public function body(): string
    {
        return $this->rawBody;
    }

    // -------------------------------------------------------------------------
    // Headers & content type
    // -------------------------------------------------------------------------

    /**
     * Return a request header value, or null if not present.
     * Header name is case-insensitive, e.g. 'Content-Type' or 'content-type'.
     */
    public function header(string $name): ?string
    {
        // PHP stores most headers under HTTP_* in $_SERVER.
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        if (isset($this->server[$key])) {
            return (string) $this->server[$key];
        }

        // Special cases not prefixed with HTTP_.
        $special = strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$special])) {
            return (string) $this->server[$special];
        }

        return null;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type') ?? '', 'application/json');
    }

    public function acceptsJson(): bool
    {
        return str_contains($this->header('Accept') ?? '', 'application/json')
            || str_contains($this->header('Accept') ?? '', '*/*');
    }

    // -------------------------------------------------------------------------
    // Attributes (route params + middleware-injected values)
    // -------------------------------------------------------------------------

    /**
     * Return a new instance with $key set to $value.
     * Does NOT mutate the original.
     */
    public function withAttribute(string $key, mixed $value): self
    {
        $clone                    = clone $this;
        $clone->attributes[$key]  = $value;

        return $clone;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    // -------------------------------------------------------------------------
    // Files & cookies
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function files(): array
    {
        return $this->files;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    // -------------------------------------------------------------------------
    // Server
    // -------------------------------------------------------------------------

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function ip(): ?string
    {
        return isset($this->server['REMOTE_ADDR'])
            ? (string) $this->server['REMOTE_ADDR']
            : null;
    }
}
