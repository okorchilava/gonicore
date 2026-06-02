<?php

declare(strict_types=1);

namespace GoniBuilder;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Category\CategoryRepository;

final class GoniBuilderController
{
    private string $viewsDir;

    public function __construct(
        private readonly BuilderService     $builderService,
        private readonly QueryBuilder       $qb,
        private readonly LoginService       $auth,
        private readonly CategoryRepository $categories,
    ) {
        $this->viewsDir = dirname(__DIR__) . '/views';
    }

    // ── Auth guard ─────────────────────────────────────────────────────────────

    private function guard(Request $request): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($request->basePath() . '/login');
        }
        return null;
    }

    // ── Full-screen editor ─────────────────────────────────────────────────────

    /** GET /goni-builder/{id} */
    public function edit(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $id   = (int) $request->getAttribute('id');
        $page = $this->qb->table('posts')->where('id', '=', $id)->where('type', '=', 'page')->first();

        if (!$page) {
            return Response::redirect($request->basePath() . '/manage/pages');
        }

        // Load global helpers (e(), excerpt(), etc.) required by the view.
        require_once dirname(__DIR__, 3) . '/themes/default/views/helpers.php';

        $elementTypes   = $this->builderService->elementTypes();
        $elementSchemas = $this->builderService->elementSchemas();
        $cats           = $this->categories->findAll();
        $base           = $request->basePath();
        $builderData    = (string) ($page['builder_data'] ?? '');

        ob_start();
        try {
            include $this->viewsDir . '/builder.php';
            return Response::html((string) ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /** POST /goni-builder/{id}/save  (JSON body) */
    public function save(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $id   = (int) $request->getAttribute('id');
        $data = $request->json();

        $builderJson = isset($data['builder_data'])
            ? json_encode($data['builder_data'])
            : null;

        $this->qb->table('posts')->where('id', '=', $id)->update([
            'use_builder'  => 1,
            'builder_data' => $builderJson,
            'template'     => 'builder',
        ]);

        return Response::json(['ok' => true]);
    }

    /** GET /goni-builder/{id}/preview */
    public function preview(Request $request): Response
    {
        if ($r = $this->guard($request)) return $r;

        $id   = (int) $request->getAttribute('id');
        $page = $this->qb->table('posts')->where('id', '=', $id)->first();
        $html = $page ? $this->builderService->render(
            (string) ($page['builder_data'] ?? ''),
            $request->basePath()
        ) : '';

        return Response::json(['html' => $html]);
    }
}
