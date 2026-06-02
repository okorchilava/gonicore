<?php

declare(strict_types=1);

namespace GoniCore\Core\Http;

use JsonException;

/**
 * Immutable HTTP response — JSON-first.
 *
 * All mutation methods return a new instance; the original is never changed.
 *
 * Usage:
 *   return Response::json(['id' => 1, 'title' => 'Hello']);
 *   return Response::json($data, 201);
 *   return Response::error('Not found', 404);
 */
final class Response
{
    /** @param array<string, string> $headers */
    private function __construct(
        private int $status,
        private array $headers,
        private string $body,
    ) {}

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    /**
     * Build a JSON response.
     *
     * @param mixed                $data     Anything json_encode() can handle.
     * @param int                  $status   HTTP status code (default 200).
     * @param array<string, string> $headers  Extra headers to include.
     * @throws JsonException  on encoding failure.
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $body = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        return new self(
            status:  $status,
            headers: array_merge(
                ['Content-Type' => 'application/json; charset=UTF-8'],
                $headers,
            ),
            body: $body,
        );
    }

    /**
     * Build an HTML response.
     *
     * @param array<string, string> $headers  Extra headers to include.
     */
    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self(
            status:  $status,
            headers: array_merge(
                ['Content-Type' => 'text/html; charset=UTF-8'],
                $headers,
            ),
            body: $content,
        );
    }

    /**
     * Build a standard JSON error envelope.
     *
     * Response shape:
     *   { "error": true, "message": "...", "errors": { ... } }
     *
     * @param array<string, mixed> $errors  Optional field-level validation errors.
     */
    public static function error(
        string $message,
        int $status = 400,
        array $errors = [],
    ): self {
        return self::json([
            'error'   => true,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /** Convenience: 404 JSON error. */
    public static function notFound(string $message = 'Not Found'): self
    {
        return self::error($message, 404);
    }

    /** Convenience: 401 JSON error. */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::error($message, 401);
    }

    /** Convenience: 403 JSON error. */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::error($message, 403);
    }

    /**
     * Build an HTTP redirect response.
     *
     * @param int $status  302 (temporary) or 301 (permanent).
     */
    public static function redirect(string $url, int $status = 302): self
    {
        return new self(
            status:  $status,
            headers: ['Location' => $url],
            body:    '',
        );
    }

    // -------------------------------------------------------------------------
    // Fluent mutation (immutable — returns clone)
    // -------------------------------------------------------------------------

    public function withStatus(int $status): self
    {
        $clone         = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone                  = clone $this;
        $clone->headers[$name]  = $value;
        return $clone;
    }

    public function withBody(string $body): self
    {
        $clone       = clone $this;
        $clone->body = $body;
        return $clone;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function status(): int
    {
        return $this->status;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    // -------------------------------------------------------------------------
    // Emit
    // -------------------------------------------------------------------------

    /**
     * Send the HTTP status line, headers, and body to the client.
     * Call this exactly once, at the end of the request lifecycle.
     */
    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }
}
