<?php

declare(strict_types=1);

namespace GoniCore\Modules\Category;

use DateTimeImmutable;

/** Immutable Category value object. */
final readonly class Category
{
    public function __construct(
        public int               $id,
        public string            $name,
        public string            $slug,
        public ?int              $parentId,
        public DateTimeImmutable $createdAt,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:        (int)    $row['id'],
            name:      (string) $row['name'],
            slug:      (string) $row['slug'],
            parentId:  isset($row['parent_id']) ? (int) $row['parent_id'] : null,
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'parent_id'  => $this->parentId,
            'created_at' => $this->createdAt->format('c'),
        ];
    }
}
