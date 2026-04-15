<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support;

use Lazarini\LivewireDataTable\Contracts\FilterTypeContract;
use Lazarini\LivewireDataTable\Support\FilterTypes\DateRangeFilterType;
use Lazarini\LivewireDataTable\Support\FilterTypes\NumericFilterType;
use Lazarini\LivewireDataTable\Support\FilterTypes\SelectFilterType;
use Lazarini\LivewireDataTable\Support\FilterTypes\TextFilterType;

final class FilterTypeRegistry
{
    /**
     * @var array<string, FilterTypeContract>
     */
    private array $types = [];

    public function __construct()
    {
        $this->register('text', new TextFilterType());
        $this->register('select', new SelectFilterType());
        $this->register('date_range', new DateRangeFilterType());
        $this->register('numeric', new NumericFilterType());
    }

    public function register(string $name, FilterTypeContract $type): void
    {
        $this->types[$name] = $type;
    }

    public function resolve(string $name): FilterTypeContract
    {
        return $this->types[$name] ?? $this->types['text'];
    }
}
