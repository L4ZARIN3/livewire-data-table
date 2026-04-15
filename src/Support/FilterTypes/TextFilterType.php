<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support\FilterTypes;

use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\Contracts\FilterTypeContract;

final class TextFilterType implements FilterTypeContract
{
    public function apply(Builder $query, array $filter, mixed $value): void
    {
        $text = trim((string) $value);

        if ($text === '') {
            return;
        }

        $column = (string) ($filter['column'] ?? $filter['key']);
        $operator = (string) ($filter['operator'] ?? 'like');

        if ($operator === 'like') {
            $query->where($column, 'like', "%{$text}%");

            return;
        }

        $query->where($column, $operator, $text);
    }
}
