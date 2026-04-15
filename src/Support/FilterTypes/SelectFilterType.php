<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support\FilterTypes;

use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\Contracts\FilterTypeContract;

final class SelectFilterType implements FilterTypeContract
{
    public function apply(Builder $query, array $filter, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $column = (string) ($filter['column'] ?? $filter['key']);

        if (is_array($value)) {
            $query->whereIn($column, $value);

            return;
        }

        $query->where($column, $value);
    }
}
