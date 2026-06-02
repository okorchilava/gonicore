<?php

declare(strict_types=1);

namespace GoniCore\Modules\Language;

use GoniCore\Core\Database\QueryBuilder;

final class LanguageRepository
{
    private const TABLE    = 'languages';
    private const PT_TABLE = 'post_translations';

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Languages ─────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function allActive(): array
    {
        return $this->qb->table(self::TABLE)
            ->where('is_active', '=', 1)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return $this->qb->table(self::TABLE)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    public function findByCode(string $code): ?array
    {
        return $this->qb->table(self::TABLE)->where('code', '=', $code)->first();
    }

    public function defaultLanguage(): ?array
    {
        return $this->qb->table(self::TABLE)
            ->where('is_default', '=', 1)
            ->where('is_active', '=', 1)
            ->first();
    }

    public function create(string $code, string $name, string $native, string $flag): void
    {
        $this->qb->table(self::TABLE)->insert(compact('code', 'name', 'native', 'flag'));
    }

    public function update(string $code, array $data): void
    {
        $this->qb->table(self::TABLE)->where('code', '=', $code)->update($data);
    }

    public function setDefault(string $code): void
    {
        $this->qb->table(self::TABLE)->where('is_default', '=', 1)->update(['is_default' => 0]);
        $this->qb->table(self::TABLE)->where('code', '=', $code)->update(['is_default' => 1]);
    }

    public function toggleActive(string $code): void
    {
        $row = $this->findByCode($code);
        if (!$row) return;
        $this->qb->table(self::TABLE)
            ->where('code', '=', $code)
            ->update(['is_active' => $row['is_active'] ? 0 : 1]);
    }

    public function delete(string $code): void
    {
        $this->qb->table(self::TABLE)->where('code', '=', $code)->delete();
    }

    // ── Post translations ─────────────────────────────────────────────────────

    public function getTranslation(int $postId, string $langCode): ?array
    {
        return $this->qb->table(self::PT_TABLE)
            ->where('post_id', '=', $postId)
            ->where('language_code', '=', $langCode)
            ->first();
    }

    public function saveTranslation(int $postId, string $langCode, array $data): void
    {
        $existing = $this->getTranslation($postId, $langCode);
        if ($existing) {
            $this->qb->table(self::PT_TABLE)
                ->where('post_id', '=', $postId)
                ->where('language_code', '=', $langCode)
                ->update($data);
        } else {
            $this->qb->table(self::PT_TABLE)->insert(array_merge($data, [
                'post_id'       => $postId,
                'language_code' => $langCode,
            ]));
        }
    }

    /** @return list<array<string,mixed>> */
    public function getTranslationsForPost(int $postId): array
    {
        return $this->qb->table(self::PT_TABLE)
            ->where('post_id', '=', $postId)
            ->get();
    }

    public function findTranslationBySlug(string $slug, string $langCode): ?array
    {
        return $this->qb->table(self::PT_TABLE)
            ->where('slug', '=', $slug)
            ->where('language_code', '=', $langCode)
            ->first();
    }
}
