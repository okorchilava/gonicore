<?php

declare(strict_types=1);

namespace GoniCore\Modules\Post;

use GoniCore\Core\Http\HttpException;
use GoniCore\Shared\Support\Str;

final class PostService
{
    public function __construct(private readonly PostRepository $posts) {}

    // -------------------------------------------------------------------------
    // Write operations
    // -------------------------------------------------------------------------

    /**
     * Create a new post, auto-generating a unique slug from the title.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data, int $authorId): Post
    {
        $id = $this->posts->save([
            'title'       => $data['title'],
            'slug'        => $this->uniqueSlug((string) $data['title'], excludeId: null),
            'content'     => $data['content'],
            'status'      => $data['status'] ?? 'draft',
            'author_id'   => $authorId,
            'category_id' => $data['category_id'] ?? null,
        ]);

        return Post::fromRow((array) $this->posts->findById($id));
    }

    /**
     * Update an existing post.
     * Slug is regenerated only if the title changed.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): Post
    {
        $existing = $this->findOrFail($id);

        if (isset($data['title']) && $data['title'] !== $existing->title) {
            $data['slug'] = $this->uniqueSlug((string) $data['title'], excludeId: $id);
        }

        $this->posts->save(array_merge($data, ['id' => $id]));

        return Post::fromRow((array) $this->posts->findById($id));
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id);
        $this->posts->delete($id);
    }

    // -------------------------------------------------------------------------
    // Read helpers
    // -------------------------------------------------------------------------

    /** @throws HttpException 404 */
    public function findOrFail(int $id): Post
    {
        $row = $this->posts->findById($id);

        if ($row === null) {
            throw new HttpException(404, "Post #{$id} not found.");
        }

        return Post::fromRow($row);
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * Derive a slug from $title that does not already exist in the posts table.
     * Appends -1, -2, … until a free slot is found.
     */
    private function uniqueSlug(string $title, ?int $excludeId): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;

        while (true) {
            $existing = $this->posts->findBySlug($slug);

            if ($existing === null
                || ($excludeId !== null && (int) $existing['id'] === $excludeId)) {
                return $slug;
            }

            $slug = "{$base}-{$i}";
            $i++;
        }
    }
}
