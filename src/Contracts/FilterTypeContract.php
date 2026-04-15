<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterTypeContract
{
    /**
     * @param  array<string, mixed>  $filter
     */
    public function apply(Builder $query, array $filter, mixed $value): void;
}
