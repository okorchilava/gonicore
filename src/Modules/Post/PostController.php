<?php

declare(strict_types=1);

namespace GoniCore\Modules\Post;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Validation\Validator;
use GoniCore\Shared\Support\Paginator;

final class PostController
{
    public function __construct(
        private readonly PostService    $service,
        private readonly PostRepository $posts,
        private readonly Validator      $validator,
    ) {}

    /** GET /api/v1/posts */
    public function index(Request $request): Response
    {
        $page    = (int) $request->query('page',     '1');
        $perPage = (int) $request->query('per_page', '15');
        $status  = $request->query('status');

        $qb = $this->posts->query()->orderBy('created_at', 'DESC');

        if ($status !== null) {
            $qb = $qb->where('status', '=', $status);
        }

        return Response::json(Paginator::paginate($qb, $page, $perPage));
    }

    /** GET /api/v1/posts/{id} */
    public function show(Request $request): Response
    {
        $id   = (int) $request->getAttribute('id');
        $post = $this->service->findOrFail($id);

        return Response::json($post->toArray());
    }

    /** POST /api/v1/posts  — auth required */
    public function store(Request $request): Response
    {
        $data = $request->json();

        $this->validator->validate($data, [
            'title'       => 'required|string|min:3|max:500',
            'content'     => 'required|string',
            'status'      => 'nullable|in:draft,published,archived',
            'category_id' => 'nullable|int',
        ]);

        $post = $this->service->create($data, (int) $request->getAttribute('userId'));

        return Response::json($post->toArray(), 201);
    }

    /** PUT /api/v1/posts/{id}  — auth required */
    public function update(Request $request): Response
    {
        $id   = (int) $request->getAttribute('id');
        $data = $request->json();

        $this->validator->validate($data, [
            'title'       => 'nullable|string|min:3|max:500',
            'content'     => 'nullable|string',
            'status'      => 'nullable|in:draft,published,archived',
            'category_id' => 'nullable|int',
        ]);

        return Response::json($this->service->update($id, $data)->toArray());
    }

    /** DELETE /api/v1/posts/{id}  — auth required */
    public function destroy(Request $request): Response
    {
        $this->service->delete((int) $request->getAttribute('id'));
        return Response::json(['message' => 'Post deleted.']);
    }
}
