<?php

declare(strict_types=1);

namespace GoniCore\Core\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Thrown when the container cannot resolve a binding
 * (e.g. unresolvable constructor parameters, non-instantiable class).
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface {}
