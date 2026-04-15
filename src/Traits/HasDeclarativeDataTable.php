<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Traits;

trait HasDeclarativeDataTable
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function makeColumns(array $columns): array
    {
        return array_values($columns);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function makeFilters(array $filters): array
    {
        return array_values($filters);
    }
}
