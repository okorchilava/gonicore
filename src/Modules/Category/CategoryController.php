<?php

declare(strict_types=1);

namespace GoniCore\Modules\Category;

use GoniCore\Core\Http\HttpException;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Validation\Validator;
use GoniCore\Shared\Support\Str;

final class CategoryController
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly Validator          $validator,
    ) {}

    /** GET /api/v1/categories */
    public function index(Request $request): Response
    {
        $rows = $this->categories->findAll();
        return Response::json(['data' => $rows]);
    }

    /** GET /api/v1/categories/{id} */
    public function show(Request $request): Response
    {
        $id  = (int) $request->getAttribute('id');
        $row = $this->categories->findById($id);

        if ($row === null) {
            throw new HttpException(404, "Category #{$id} not found.");
        }

        return Response::json(Category::fromRow($row)->toArray());
    }

    /** POST /api/v1/categories  — auth required */
    public function store(Request $request): Response
    {
        $data = $request->json();

        $this->validator->validate($data, [
            'name'      => 'required|string|min:2|max:100',
            'parent_id' => 'nullable|int',
        ]);

        $slug = Str::slug((string) $data['name']);

        if ($this->categories->findBySlug($slug) !== null) {
            throw new HttpException(409, 'A category with this name already exists.');
        }

        $id = $this->categories->save([
            'name'      => $data['name'],
            'slug'      => $slug,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return Response::json(Category::fromRow((array) $this->categories->findById($id))->toArray(), 201);
    }

    /** PUT /api/v1/categories/{id}  — auth required */
    public function update(Request $request): Response
    {
        $id  = (int) $request->getAttribute('id');
        $row = $this->categories->findById($id);

        if ($row === null) {
            throw new HttpException(404, "Category #{$id} not found.");
        }

        $data = $request->json();

        $this->validator->validate($data, [
            'name'      => 'nullable|string|min:2|max:100',
            'parent_id' => 'nullable|int',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug((string) $data['name']);
        }

        $this->categories->save(array_merge($data, ['id' => $id]));

        return Response::json(Category::fromRow((array) $this->categories->findById($id))->toArray());
    }

    /** DELETE /api/v1/categories/{id}  — auth required */
    public function destroy(Request $request): Response
    {
        $id = (int) $request->getAttribute('id');

        if (!$this->categories->delete($id)) {
            throw new HttpException(404, "Category #{$id} not found.");
        }

        return Response::json(['message' => 'Category deleted.']);
    }
}
