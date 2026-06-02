<?php

declare(strict_types=1);

namespace GoniCore\Core\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when no binding exists for the requested identifier
 * and the class cannot be auto-wired.
 */
final class NotFoundException extends ContainerException implements NotFoundExceptionInterface {}
