<?php

declare(strict_types=1);

namespace GoniCore\Core\Database;

use RuntimeException;

/**
 * Thrown for any database-level error: connection failures,
 * failed prepares, and query execution errors.
 */
final class DatabaseException extends RuntimeException {}
