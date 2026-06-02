<?php

declare(strict_types=1);

use GoniCore\Modules\Menu\MenuService;
use GoniCore\Modules\Widget\WidgetService;

/**
 * GoniCore Default Theme — functions.php
 *
 * Loaded once during bootstrap (after the DI container is ready).
 * Use this file to:
 *   - Register widget areas this theme supports
 *   - Register custom widget types
 *   - Add action/filter hooks
 */

// ── Widget areas ──────────────────────────────────────────────────────────────

// ── Menu locations ────────────────────────────────────────────────────────────

MenuService::registerLocation('primary',  'Primary Navigation');
MenuService::registerLocation('footer',   'Footer Links');
MenuService::registerLocation('mobile',   'Mobile Menu');

// ── Widget areas ──────────────────────────────────────────────────────────────

WidgetService::registerArea(
    slug:        'sidebar',
    name:        'Main Sidebar',
    description: 'Appears on the right side of posts and archive pages.',
);

WidgetService::registerArea(
    slug:        'header-bar',
    name:        'Header Bar',
    description: 'Small strip just below the site navigation.',
);

WidgetService::registerArea(
    slug:        'footer-col-1',
    name:        'Footer — Column 1',
    description: 'First column of the site footer.',
);

WidgetService::registerArea(
    slug:        'footer-col-2',
    name:        'Footer — Column 2',
    description: 'Second column of the site footer.',
);

WidgetService::registerArea(
    slug:        'footer-col-3',
    name:        'Footer — Column 3',
    description: 'Third column of the site footer.',
);
