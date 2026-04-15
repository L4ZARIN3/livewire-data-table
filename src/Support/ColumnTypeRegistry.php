<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Support;

use Lazarini\LivewireDataTable\Contracts\ColumnTypeContract;
use Lazarini\LivewireDataTable\Support\ColumnTypes\BadgeColumnType;
use Lazarini\LivewireDataTable\Support\ColumnTypes\DateTimeColumnType;
use Lazarini\LivewireDataTable\Support\ColumnTypes\TextColumnType;

final class ColumnTypeRegistry
{
    /**
     * @var array<string, ColumnTypeContract>
     */
    private array $types = [];

    public function __construct()
    {
        $this->register('text', new TextColumnType());
        $this->register('datetime', new DateTimeColumnType());
        $this->register('badge', new BadgeColumnType());
    }

    public function register(string $name, ColumnTypeContract $type): void
    {
        $this->types[$name] = $type;
    }

    public function resolve(string $name): ColumnTypeContract
    {
        return $this->types[$name] ?? $this->types['text'];
    }
}
