<?php

declare(strict_types=1);

namespace GoniCore\Core\Http;

use RuntimeException;
use Throwable;

/**
 * Represents an HTTP error that should be converted to a JSON error response.
 *
 * Example:
 *   throw new HttpException(404, 'Post not found.');
 *   throw new HttpException(422, 'Validation failed.', ['title' => 'required']);
 */
final class HttpException extends RuntimeException
{
    /**
     * @param int                  $statusCode  HTTP status code (4xx / 5xx).
     * @param string               $message     Human-readable error message.
     * @param array<string, mixed> $errors      Optional field-level error details.
     * @param Throwable|null       $previous    Wrapped cause, if any.
     */
    public function __construct(
        int $statusCode,
        string $message = '',
        private readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /** @return array<string, mixed> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
