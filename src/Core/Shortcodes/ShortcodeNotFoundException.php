<?php

declare(strict_types=1);

namespace GoniCore\Core\Shortcodes;

use RuntimeException;

/**
 * Thrown when ShortcodeManager is asked to execute an unregistered tag.
 */
final class ShortcodeNotFoundException extends RuntimeException {}
