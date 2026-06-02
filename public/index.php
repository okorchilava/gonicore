<?php

declare(strict_types=1);

/**
 * GoniCore — Front Controller
 *
 * All HTTP traffic is routed through this file.
 * Point your web server document root to the /public directory.
 *
 * Apache (.htaccess in /public):
 *   RewriteEngine On
 *   RewriteCond %{REQUEST_FILENAME} !-f
 *   RewriteRule ^ index.php [L]
 *
 * Nginx:
 *   try_files $uri $uri/ /index.php?$query_string;
 */

// ── Installation gate ─────────────────────────────────────────
// If the system has never been installed, redirect to the web
// installer.  The installer creates .installed upon completion,
// so this redirect fires exactly once.
if (!is_file(dirname(__DIR__) . '/.installed')) {
    $base = rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    header('Location: ' . $base . '/install.php', true, 302);
    exit;
}

// ── Autoloader ────────────────────────────────────────────────
require __DIR__ . '/../vendor/autoload.php';

/** @var \GoniCore\Core\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';

$app->run();
