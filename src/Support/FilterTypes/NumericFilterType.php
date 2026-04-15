<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support\FilterTypes;

use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\Contracts\FilterTypeContract;

final class NumericFilterType implements FilterTypeContract
{
    public function apply(Builder $query, array $filter, mixed $value): void
    {
        $column = (string) ($filter['column'] ?? $filter['key']);

        if (is_array($value)) {
            $min = $value['min'] ?? null;
            $max = $value['max'] ?? null;

            if ($min !== null && $min !== '') {
                $query->where($column, '>=', $min);
            }

            if ($max !== null && $max !== '') {
                $query->where($column, '<=', $max);
            }

            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $operator = (string) ($filter['operator'] ?? '=');
        $query->where($column, $operator, $value);
    }
}
