<?php

declare(strict_types=1);

namespace GoniCore\Core\Validation;

use GoniCore\Core\Http\HttpException;

/**
 * Thrown when input validation fails.
 * Automatically produces a 422 JSON response via Application::handle().
 */
final class ValidationException extends HttpException
{
    /**
     * @param array<string, list<string>> $errors  Field → list of error messages.
     */
    public function __construct(array $errors)
    {
        parent::__construct(422, 'Validation failed.', $errors);
    }
}
