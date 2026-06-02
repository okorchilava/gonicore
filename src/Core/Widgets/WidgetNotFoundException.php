<?php

declare(strict_types=1);

namespace GoniCore\Core\Widgets;

use RuntimeException;

/**
 * Thrown by WidgetManager when a widget ID has not been registered.
 */
final class WidgetNotFoundException extends RuntimeException {}
