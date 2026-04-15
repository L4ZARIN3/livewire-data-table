<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support\FilterTypes;

use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\Contracts\FilterTypeContract;

final class DateRangeFilterType implements FilterTypeContract
{
    public function apply(Builder $query, array $filter, mixed $value): void
    {
        $column = (string) ($filter['column'] ?? $filter['key']);
        $from = trim((string) (is_array($value) ? ($value['from'] ?? '') : ''));
        $to = trim((string) (is_array($value) ? ($value['to'] ?? '') : ''));

        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($from !== '') {
            $query->whereDate($column, '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate($column, '<=', $to);
        }
    }
}
