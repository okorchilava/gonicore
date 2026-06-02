<?php

declare(strict_types=1);

namespace GoniCore\Core\Hooks;

use GoniCore\Core\Container\Container;
use GoniCore\Core\Http\Router;
use GoniCore\Core\Hooks\HookManager;
use Throwable;

/**
 * Scans a directory for plugin sub-folders and boots each one safely.
 *
 * Convention: each plugin lives in its own directory and MUST contain
 * a `bootstrap.php` entry-point file.
 *
 * plugins/
 *   my-plugin/
 *     bootstrap.php   ← required
 *     src/
 *     ...
 *
 * A plugin that throws during bootstrap is logged to the PHP error log
 * and skipped — it will NOT crash the rest of the application.
 */
final class PluginLoader
{
    /**
     * Scan $pluginsDirectory, find plugin folders, and require their
     * bootstrap.php files in filesystem order.
     *
     * Each bootstrap.php runs with the following variables in scope:
     *   $router    — the application Router (register routes here)
     *   $container — the DI Container (resolve / bind services here)
     *   $hooks     — the HookManager (add actions / filters here)
     *   $pluginDir — absolute path to the plugin's own directory
     *
     * @param string     $pluginsDirectory Absolute path to the plugins/ directory.
     * @param Router     $router           Application router.
     * @param Container  $container        DI container.
     * @param HookManager $hooks           Hook / filter dispatcher.
     */
    public function load(
        string      $pluginsDirectory,
        Router      $router,
        Container   $container,
        HookManager $hooks,
    ): void {
        if (!is_dir($pluginsDirectory)) {
            return;
        }

        $entries = scandir($pluginsDirectory);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $pluginDir = $pluginsDirectory . DIRECTORY_SEPARATOR . $entry;

            if (!is_dir($pluginDir)) {
                continue;
            }

            // Respect the .disabled marker that PluginManager writes
            if (file_exists($pluginDir . DIRECTORY_SEPARATOR . '.disabled')) {
                continue;
            }

            $bootstrap = $pluginDir . DIRECTORY_SEPARATOR . 'bootstrap.php';

            if (!is_file($bootstrap)) {
                continue;
            }

            $this->bootPlugin($entry, $pluginDir, $bootstrap, $router, $container, $hooks);
        }
    }

    /**
     * Require the plugin's bootstrap file inside a try/catch so that a
     * single broken plugin cannot take down the entire application.
     *
     * Variables made available to bootstrap.php via method scope:
     *   $router, $container, $hooks, $pluginDir
     */
    private function bootPlugin(
        string      $pluginName,
        string      $pluginDir,      // available in bootstrap.php as $pluginDir
        string      $bootstrapFile,
        Router      $router,         // available in bootstrap.php as $router
        Container   $container,      // available in bootstrap.php as $container
        HookManager $hooks,          // available in bootstrap.php as $hooks
    ): void {
        try {
            require $bootstrapFile;
        } catch (Throwable $e) {
            error_log(sprintf(
                '[GoniCore] Plugin "%s" failed to boot: %s in %s on line %d',
                $pluginName,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            ));
        }
    }
}
