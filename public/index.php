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

// ── Setup / installation gate ─────────────────────────────────
// A site is considered "set up" only when BOTH exist:
//   • composer.lock + vendor/  → dependencies installed
//   • .env                     → application configured
// If either is missing, send the visitor somewhere useful instead
// of letting the app fatal with a blank/white screen.
$root = dirname(__DIR__);
$base = rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// 1) Dependencies not installed — the installer can't fix this
//    (composer.lock is produced by Composer, not the wizard).
if (!is_file($root . '/composer.lock') || !is_file($root . '/vendor/autoload.php')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit("GoniCore is not ready: dependencies are missing.\nRun \"composer install\" in the project root, then reload.");
}

// 2) Not configured yet → run the self-contained web installer,
//    which writes .env on completion.
if (!is_file($root . '/.env')) {
    header('Location: ' . $base . '/install.php', true, 302);
    exit;
}

// ── Autoloader ────────────────────────────────────────────────
require $root . '/vendor/autoload.php';

/** @var \GoniCore\Core\Application $app */
$app = require $root . '/bootstrap/app.php';

$app->run();
