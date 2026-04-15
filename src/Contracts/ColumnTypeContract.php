<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Contracts;

interface ColumnTypeContract
{
    /**
     * @param  array<string, mixed>  $column
     * @param  mixed  $rawValue
     */
    public function format(array $column, mixed $rawValue): string;
}
