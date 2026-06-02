<?php

declare(strict_types=1);

namespace GoniCore\Modules\Post;

use DateTimeImmutable;

/** Immutable Post value object. */
final readonly class Post
{
    public function __construct(
        public int               $id,
        public string            $title,
        public string            $slug,
        public string            $content,
        public string            $status,      // draft | published | archived
        public int               $authorId,
        public ?int              $categoryId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:         (int)    $row['id'],
            title:      (string) $row['title'],
            slug:       (string) $row['slug'],
            content:    (string) $row['content'],
            status:     (string) $row['status'],
            authorId:   (int)    $row['author_id'],
            categoryId: isset($row['category_id']) ? (int) $row['category_id'] : null,
            createdAt:  new DateTimeImmutable((string) $row['created_at']),
            updatedAt:  new DateTimeImmutable((string) $row['updated_at']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'content'     => $this->content,
            'status'      => $this->status,
            'author_id'   => $this->authorId,
            'category_id' => $this->categoryId,
            'created_at'  => $this->createdAt->format('c'),
            'updated_at'  => $this->updatedAt->format('c'),
        ];
    }

    public function isPublished(): bool { return $this->status === 'published'; }
    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isArchived(): bool  { return $this->status === 'archived'; }
}
