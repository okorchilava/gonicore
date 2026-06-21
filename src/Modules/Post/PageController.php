<?php

declare(strict_types=1);

namespace GoniCore\Modules\Post;

use GoniCore\Core\Http\HttpException;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Validation\Validator;
use GoniCore\Shared\Support\Paginator;

/**
 * REST API controller for CMS Pages.
 *
 * Public:
 *   GET  /api/v1/pages              List published pages (paginated)
 *   GET  /api/v1/pages/{id}         Get a single published page by ID
 *   GET  /api/v1/pages/slug/{slug}  Get a page by slug
 *
 * Auth required:
 *   POST   /api/v1/pages            Create page
 *   PUT    /api/v1/pages/{id}       Update page
 *   DELETE /api/v1/pages/{id}       Delete page
 *
 * Note: the DB column is `type` (added by migration 0013), NOT `post_type`.
 */
final class PageController
{
    public function __construct(
        private readonly PostRepository $repo,
        private readonly Validator      $validator,
    ) {}

    /** GET /api/v1/pages */
    public function index(Request $request): Response
    {
        $page    = max(1, (int) $request->query('page',     '1'));
        $perPage = min(100, max(1, (int) $request->query('per_page', '15')));

        $qb = $this->repo->query()
            ->where('type', '=', 'page')
            ->where('status', '=', 'published')
            ->orderBy('created_at', 'DESC');

        return Response::json(Paginator::paginate($qb, $page, $perPage));
    }

    /** GET /api/v1/pages/{id} */
    public function show(Request $request): Response
    {
        $id   = (int) $request->getAttribute('id');
        $page = $this->repo->findById($id);

        if (!$page || ($page['type'] ?? '') !== 'page' || $page['status'] !== 'published') {
            throw new HttpException(404, "Page #{$id} not found.");
        }

        return Response::json($page);
    }

    /** GET /api/v1/pages/slug/{slug} */
    public function showBySlug(Request $request): Response
    {
        $slug = (string) $request->getAttribute('slug');
        $page = $this->repo->findBySlug($slug);

        if (!$page || ($page['type'] ?? '') !== 'page' || $page['status'] !== 'published') {
            throw new HttpException(404, "Page \"{$slug}\" not found.");
        }

        return Response::json($page);
    }

    /** POST /api/v1/pages  — auth required */
    public function store(Request $request): Response
    {
        $data = $request->json();

        $this->validator->validate($data, [
            'title'    => 'required|string|min:1|max:500',
            'content'  => 'nullable|string',
            'slug'     => 'nullable|string|max:255',
            'status'   => 'nullable|in:draft,published,archived',
            'template' => 'nullable|string|max:100',
        ]);

        $title = (string) ($data['title'] ?? '');
        $slug  = (isset($data['slug']) && $data['slug'] !== '')
            ? (string) $data['slug']
            : strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));

        $id = $this->repo->save([
            'type'      => 'page',
            'title'     => $title,
            'slug'      => $slug,
            'content'   => (string) ($data['content'] ?? ''),
            'status'    => (string) ($data['status'] ?? 'draft'),
            'template'  => (string) ($data['template'] ?? 'default'),
            'author_id' => (int) $request->getAttribute('userId'),
        ]);

        return Response::json($this->repo->findById((int) $id), 201);
    }

    /** PUT /api/v1/pages/{id}  — auth required */
    public function update(Request $request): Response
    {
        $id   = (int) $request->getAttribute('id');
        $page = $this->repo->findById($id);

        if (!$page || ($page['type'] ?? '') !== 'page') {
            throw new HttpException(404, "Page #{$id} not found.");
        }

        $data = $request->json();

        $this->validator->validate($data, [
            'title'    => 'nullable|string|min:1|max:500',
            'content'  => 'nullable|string',
            'slug'     => 'nullable|string|max:255',
            'status'   => 'nullable|in:draft,published,archived',
            'template' => 'nullable|string|max:100',
        ]);

        $fields = [];
        foreach (['title', 'content', 'slug', 'status', 'template'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[$key] = (string) $data[$key];
            }
        }

        if (!empty($fields)) {
            $this->repo->save(['id' => $id] + $fields);
        }

        return Response::json($this->repo->findById($id));
    }

    /** DELETE /api/v1/pages/{id}  — auth required */
    public function destroy(Request $request): Response
    {
        $id   = (int) $request->getAttribute('id');
        $page = $this->repo->findById($id);

        if (!$page || ($page['type'] ?? '') !== 'page') {
            throw new HttpException(404, "Page #{$id} not found.");
        }

        $this->repo->delete($id);

        return Response::json(['message' => "Page #{$id} deleted."]);
    }
}
