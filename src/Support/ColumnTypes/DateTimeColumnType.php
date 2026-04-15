<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support\ColumnTypes;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Lazarini\LivewireDataTable\Contracts\ColumnTypeContract;

final class DateTimeColumnType implements ColumnTypeContract
{
    public function format(array $column, mixed $rawValue): string
    {
        if (! $rawValue instanceof DateTimeInterface) {
            return (string) ($column['placeholder'] ?? '—');
        }

        $format = (string) ($column['format'] ?? 'd/m/Y H:i');

        if ($rawValue instanceof CarbonInterface) {
            return $rawValue->translatedFormat($format);
        }

        return $rawValue->format($format);
    }
}
