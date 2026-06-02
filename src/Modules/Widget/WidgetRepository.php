<?php

declare(strict_types=1);

namespace GoniCore\Modules\Widget;

use GoniCore\Core\Database\QueryBuilder;

final class WidgetRepository
{
    private const TABLE = 'widgets';

    public function __construct(private readonly QueryBuilder $qb) {}

    /** @return list<array<string,mixed>> */
    public function forArea(string $area): array
    {
        return $this->qb->table(self::TABLE)
            ->where('area', '=', $area)
            ->where('is_active', '=', 1)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return $this->qb->table(self::TABLE)->orderBy('area', 'ASC')->orderBy('sort_order', 'ASC')->get();
    }

    public function findById(int $id): ?array
    {
        return $this->qb->table(self::TABLE)->where('id', '=', $id)->first();
    }

    public function create(string $area, string $type, ?string $title, array $settings): int
    {
        $maxOrder = count($this->forArea($area));
        return (int) $this->qb->table(self::TABLE)->insert([
            'area'       => $area,
            'type'       => $type,
            'title'      => $title,
            'settings'   => json_encode($settings),
            'sort_order' => $maxOrder,
        ]);
    }

    public function update(int $id, ?string $title, array $settings): void
    {
        $this->qb->table(self::TABLE)->where('id', '=', $id)->update([
            'title'    => $title,
            'settings' => json_encode($settings),
        ]);
    }

    public function toggle(int $id): void
    {
        $row = $this->findById($id);
        if (!$row) return;
        $this->qb->table(self::TABLE)->where('id', '=', $id)->update(['is_active' => $row['is_active'] ? 0 : 1]);
    }

    public function delete(int $id): void
    {
        $this->qb->table(self::TABLE)->where('id', '=', $id)->delete();
    }

    public function reorder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            $this->qb->table(self::TABLE)->where('id', '=', (int)$id)->update(['sort_order' => $order]);
        }
    }
}
