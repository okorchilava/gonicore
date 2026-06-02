<?php

declare(strict_types=1);

use GoniCore\Core\Container\Container;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Http\Router;
use GoniCore\Core\Http\Middleware\AuthMiddleware;
use GoniCore\Modules\Auth\AuthController;
use GoniCore\Modules\Category\CategoryController;
use GoniCore\Modules\Media\MediaController;
use GoniCore\Modules\Post\PostController;
use GoniCore\Modules\Widget\WidgetController;

/**
 * @var Router    $router
 * @var Container $container
 */

$router->group('/api/v1', static function (Router $router) use ($container): void {

    $router->get('/health', static fn() => Response::json([
        'status'  => 'ok',
        'version' => '1.0.0',
    ]));

    // Auth
    $router->post('/auth/register', [AuthController::class, 'register']);
    $router->post('/auth/login',    [AuthController::class, 'login']);
    $router->get('/auth/me',        [AuthController::class, 'me'])
        ->middleware($container->get(AuthMiddleware::class));

    // Posts
    $router->get('/posts',      [PostController::class, 'index']);
    $router->get('/posts/{id}', [PostController::class, 'show']);
    $router->post('/posts',        [PostController::class, 'store'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->put('/posts/{id}',    [PostController::class, 'update'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->delete('/posts/{id}', [PostController::class, 'destroy'])
        ->middleware($container->get(AuthMiddleware::class));

    // Categories
    $router->get('/categories',      [CategoryController::class, 'index']);
    $router->get('/categories/{id}', [CategoryController::class, 'show']);
    $router->post('/categories',        [CategoryController::class, 'store'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->put('/categories/{id}',    [CategoryController::class, 'update'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->delete('/categories/{id}', [CategoryController::class, 'destroy'])
        ->middleware($container->get(AuthMiddleware::class));

    // Widgets — public headless render
    $router->get('/widgets/render/{id}', [WidgetController::class, 'render']);

    // Widgets — admin CRUD (auth required)
    $router->get('/widgets',              [WidgetController::class, 'index'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->get('/widgets/area/{area}',  [WidgetController::class, 'area'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->post('/widgets',             [WidgetController::class, 'store'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->post('/widgets/reorder',     [WidgetController::class, 'reorder'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->patch('/widgets/{id}',       [WidgetController::class, 'update'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->post('/widgets/{id}/toggle', [WidgetController::class, 'toggle'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->delete('/widgets/{id}',      [WidgetController::class, 'destroy'])
        ->middleware($container->get(AuthMiddleware::class));

    // Media
    $router->get('/media/{id}',    [MediaController::class, 'show']);
    $router->post('/media',        [MediaController::class, 'upload'])
        ->middleware($container->get(AuthMiddleware::class));
    $router->delete('/media/{id}', [MediaController::class, 'destroy'])
        ->middleware($container->get(AuthMiddleware::class));
});
