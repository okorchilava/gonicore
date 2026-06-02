<?php

declare(strict_types=1);

use GoniCore\Core\Container\Container;
use GoniCore\Core\Http\Router;
use GoniCore\Modules\Language\LanguageController;
use GoniCore\Modules\Login\LoginController;
use GoniCore\Modules\Manage\ManageController;
use GoniCore\Modules\Theme\ThemeController;

/**
 * @var Router    $router
 * @var Container $container
 */

// ── Front-end (theme) routes ─────────────────────────────────
// These sit alongside the API routes — no prefix conflict since
// the API always uses /api/v1/ and the theme uses clean slugs.

$router->get('/',                 [ThemeController::class, 'home']);
$router->get('/post/{slug}',      [ThemeController::class, 'post']);
$router->get('/page/{slug}',      [ThemeController::class, 'page']);
$router->get('/category/{slug}',  [ThemeController::class, 'category']);

// ── Language switch (public) ──────────────────────────────────
$router->get('/lang/{code}', [LanguageController::class, 'switchLang']);

// ── Auth (web) ────────────────────────────────────────────────
$router->get('/login',  [LoginController::class, 'showLogin']);
$router->post('/login', [LoginController::class, 'processLogin']);
$router->get('/logout', [LoginController::class, 'logout']);

// ── Manage panel ──────────────────────────────────────────────
$router->group('/manage', static function (Router $router): void {
    $router->get('',              [ManageController::class, 'dashboard']);
    $router->get('/posts',        [ManageController::class, 'postsList']);
    $router->get('/posts/new',    [ManageController::class, 'postNew']);
    $router->post('/posts',       [ManageController::class, 'postCreate']);
    $router->get('/posts/{id}',   [ManageController::class, 'postEdit']);
    $router->post('/posts/{id}',  [ManageController::class, 'postUpdate']);
    $router->post('/posts/{id}/delete', [ManageController::class, 'postDelete']);
    $router->get('/users',                        [ManageController::class, 'usersList']);
    $router->get('/users/new',                    [ManageController::class, 'userNew']);
    $router->post('/users/new',                   [ManageController::class, 'userCreate']);
    $router->get('/users/{id}/edit',              [ManageController::class, 'userEdit']);
    $router->post('/users/{id}/edit',             [ManageController::class, 'userUpdate']);
    $router->post('/users/{id}/delete',           [ManageController::class, 'userDelete']);
    $router->get('/profile',                      [ManageController::class, 'profileForm']);
    $router->post('/profile',                     [ManageController::class, 'profileSave']);
    $router->post('/todos',             [ManageController::class, 'todoCreate']);
    $router->post('/todos/{id}/toggle', [ManageController::class, 'todoToggle']);
    $router->post('/todos/{id}/delete', [ManageController::class, 'todoDelete']);
    $router->post('/notifications/{id}/read', [ManageController::class, 'notificationRead']);
    $router->post('/notifications/read-all',  [ManageController::class, 'notificationReadAll']);

    // Languages
    $router->get('/settings',  [ManageController::class, 'settingsForm']);
    $router->post('/settings', [ManageController::class, 'settingsSave']);

    // Pages
    $router->get('/pages',              [ManageController::class, 'pagesList']);
    $router->get('/pages/new',          [ManageController::class, 'pageNew']);
    $router->post('/pages',             [ManageController::class, 'pageCreate']);
    $router->get('/pages/{id}',         [ManageController::class, 'pageEdit']);
    $router->post('/pages/{id}',        [ManageController::class, 'pageUpdate']);
    $router->post('/pages/{id}/delete', [ManageController::class, 'pageDelete']);

    $router->get('/languages',                      [LanguageController::class, 'index']);
    $router->post('/languages',                     [LanguageController::class, 'store']);
    $router->get('/languages/{code}/edit',          [LanguageController::class, 'editForm']);
    $router->post('/languages/{code}/edit',         [LanguageController::class, 'editSave']);
    $router->post('/languages/{code}/default',      [LanguageController::class, 'setDefault']);
    $router->post('/languages/{code}/toggle',       [LanguageController::class, 'toggle']);
    $router->post('/languages/{code}/delete',       [LanguageController::class, 'delete']);
    $router->get('/posts/{id}/translate/{code}',    [LanguageController::class, 'translateForm']);
    $router->post('/posts/{id}/translate/{code}',   [LanguageController::class, 'translateSave']);

    // Menus
    $router->get('/menus',                            [ManageController::class, 'menusList']);
    $router->post('/menus/create',                    [ManageController::class, 'menuCreate']);
    $router->post('/menus/{id}/delete',               [ManageController::class, 'menuDelete']);
    $router->post('/menus/{id}/rename',               [ManageController::class, 'menuRename']);
    $router->post('/menus/assign-locations',          [ManageController::class, 'menuAssignLocations']);
    $router->post('/menus/{id}/items/add',            [ManageController::class, 'menuItemAdd']);
    $router->post('/menus/items/{item_id}/update',    [ManageController::class, 'menuItemUpdate']);
    $router->post('/menus/items/{item_id}/delete',    [ManageController::class, 'menuItemDelete']);
    $router->post('/menus/items/reorder',             [ManageController::class, 'menuItemReorder']);

    // Categories (admin manage)
    $router->get('/categories',                       [ManageController::class, 'categoriesList']);
    $router->post('/categories/create',               [ManageController::class, 'categoryCreate']);
    $router->post('/categories/{id}/update',          [ManageController::class, 'categoryUpdate']);
    $router->post('/categories/{id}/delete',          [ManageController::class, 'categoryDelete']);

    // Gallery
    $router->get('/gallery',                        [ManageController::class, 'galleryList']);
    $router->get('/gallery/json',                   [ManageController::class, 'galleryJson']);
    $router->post('/gallery/upload',                [ManageController::class, 'galleryUpload']);
    $router->post('/gallery/{id}/delete',           [ManageController::class, 'galleryDelete']);

    // Widgets
    $router->get('/widgets',                        [ManageController::class, 'widgetsList']);
    $router->post('/widgets',                       [ManageController::class, 'widgetCreate']);
    $router->post('/widgets/{id}',                  [ManageController::class, 'widgetUpdate']);
    $router->post('/widgets/{id}/toggle',           [ManageController::class, 'widgetToggle']);
    $router->post('/widgets/{id}/delete',           [ManageController::class, 'widgetDelete']);

    // Plugins
    $router->get('/plugins',                        [ManageController::class, 'pluginsList']);
    $router->post('/plugins/upload',                [ManageController::class, 'pluginUpload']);
    $router->post('/plugins/{slug}/activate',       [ManageController::class, 'pluginActivate']);
    $router->post('/plugins/{slug}/deactivate',     [ManageController::class, 'pluginDeactivate']);
    $router->post('/plugins/{slug}/delete',         [ManageController::class, 'pluginDelete']);
});

