<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support\ColumnTypes;

use Lazarini\LivewireDataTable\Contracts\ColumnTypeContract;

final class BadgeColumnType implements ColumnTypeContract
{
    public function format(array $column, mixed $rawValue): string
    {
        if ($rawValue === null || $rawValue === '') {
            return (string) ($column['placeholder'] ?? '—');
        }

        $map = is_array($column['options'] ?? null) ? $column['options'] : [];

        return (string) ($map[(string) $rawValue] ?? $rawValue);
    }
}
