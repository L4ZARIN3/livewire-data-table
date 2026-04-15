<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Lazarini\LivewireDataTable\Support\ColumnTypeRegistry;
use Lazarini\LivewireDataTable\Support\FilterTypeRegistry;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Isolate]
abstract class DataTableComponent extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortField = '';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    #[Url(as: 'pp')]
    public int $perPage = 10;

    /**
     * @var array<string, mixed>
     */
    public array $filterValues = [];

    public ?string $expandedRowKey = null;

    abstract protected function tableQuery(): Builder;

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function columns(): array
    {
        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function filters(): array
    {
        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function details(): array
    {
        return [];
    }

    protected function title(): string
    {
        return 'Tabela dinâmica';
    }

    protected function subtitle(): ?string
    {
        return null;
    }

    /**
     * @return array<int, int>
     */
    protected function perPageOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function minPerPage(): int
    {
        return 5;
    }

    protected function maxPerPage(): int
    {
        return 100;
    }

    public function mount(): void
    {
        $this->bootFilterValues();

        if ($this->sortField === '') {
            $sortableColumn = collect($this->normalizedColumns())
                ->first(fn (array $column): bool => (bool) $column['sortable']);
            $this->sortField = (string) ($sortableColumn['key'] ?? 'id');
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'filterValues.') || $property === 'search' || $property === 'perPage') {
            $this->expandedRowKey = null;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->filterValues = [];
        $this->bootFilterValues();
        $this->search = '';
        $this->expandedRowKey = null;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $column = collect($this->normalizedColumns())
            ->first(fn (array $column): bool => $column['key'] === $field);

        if (! is_array($column) || ! $column['sortable']) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->expandedRowKey = null;
        $this->resetPage();
    }

    public function toggleExpandedRow(string|int $rowKey): void
    {
        $rowKey = (string) $rowKey;
        $this->expandedRowKey = $this->expandedRowKey === $rowKey ? null : $rowKey;
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = $this->tableQuery();
        $this->applySelectColumns($query);
        $this->applySearch($query);
        $this->applyFilters($query);
        $this->applySorting($query);
        $this->applyWith($query);

        return $query->paginate($this->clampedPerPage());
    }

    public function render(): View
    {
        return view('livewire-data-table::table', [
            'columns' => $this->normalizedColumns(),
            'filters' => $this->normalizedFilters(),
            'details' => $this->details(),
            'rows' => $this->rows,
            'perPageOptions' => $this->perPageOptions(),
            'title' => $this->title(),
            'subtitle' => $this->subtitle(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $column
     */
    public function cellValue(Model $row, array $column): mixed
    {
        $value = data_get($row, $column['key']);

        if (is_callable($column['value'] ?? null)) {
            $value = $column['value']($row, $column, $this);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $column
     */
    public function renderCell(Model $row, array $column): string
    {
        $value = $this->cellValue($row, $column);

        if (is_callable($column['format'] ?? null)) {
            return (string) $column['format']($value, $row, $column, $this);
        }

        $type = $this->columnTypes()->resolve((string) $column['type']);

        return $type->format($column, $value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizedColumns(): array
    {
        return collect($this->columns())
            ->map(function (array $column): array {
                $column['key'] = (string) ($column['key'] ?? '');
                $column['label'] = (string) ($column['label'] ?? $column['key']);
                $column['type'] = (string) ($column['type'] ?? 'text');
                $column['sortable'] = (bool) ($column['sortable'] ?? false);
                $column['searchable'] = (bool) ($column['searchable'] ?? false);
                $column['sort_column'] = (string) ($column['sort_column'] ?? $column['key']);
                $column['header_class'] = (string) ($column['header_class'] ?? '');
                $column['cell_class'] = (string) ($column['cell_class'] ?? '');
                $column['view'] = $column['view'] ?? null;
                $column['placeholder'] = (string) ($column['placeholder'] ?? '—');

                return $column;
            })
            ->filter(fn (array $column): bool => $column['key'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizedFilters(): array
    {
        return collect($this->filters())
            ->map(function (array $filter): array {
                $filter['key'] = (string) ($filter['key'] ?? '');
                $filter['type'] = (string) ($filter['type'] ?? 'text');
                $filter['label'] = (string) ($filter['label'] ?? $filter['key']);
                $filter['placeholder'] = (string) ($filter['placeholder'] ?? '');
                $filter['column'] = (string) ($filter['column'] ?? $filter['key']);
                $filter['options'] = is_array($filter['options'] ?? null) ? $filter['options'] : [];
                $filter['operator'] = (string) ($filter['operator'] ?? '=');
                $filter['class'] = (string) ($filter['class'] ?? '');

                return $filter;
            })
            ->filter(fn (array $filter): bool => $filter['key'] !== '')
            ->values()
            ->all();
    }

    protected function bootFilterValues(): void
    {
        foreach ($this->normalizedFilters() as $filter) {
            if (array_key_exists($filter['key'], $this->filterValues)) {
                continue;
            }

            $this->filterValues[$filter['key']] = match ($filter['type']) {
                'date_range' => ['from' => '', 'to' => ''],
                'numeric' => ['min' => '', 'max' => ''],
                default => '',
            };
        }
    }

    protected function applySearch(Builder $query): void
    {
        $term = trim($this->search);

        if ($term === '') {
            return;
        }

        $columns = collect($this->normalizedColumns())
            ->filter(fn (array $column): bool => $column['searchable'])
            ->map(fn (array $column): string => (string) $column['sort_column'])
            ->values();

        if ($columns->isEmpty()) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($columns, $term): void {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $searchQuery->where($column, 'like', "%{$term}%");

                    continue;
                }

                $searchQuery->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    protected function applyFilters(Builder $query): void
    {
        foreach ($this->normalizedFilters() as $filter) {
            $key = $filter['key'];
            $value = $this->filterValues[$key] ?? null;

            if (is_callable($filter['apply'] ?? null)) {
                $filter['apply']($query, $value, $filter, $this);

                continue;
            }

            $this->filterTypes()->resolve((string) $filter['type'])->apply($query, $filter, $value);
        }
    }

    protected function applySorting(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $column = collect($this->normalizedColumns())
            ->first(fn (array $column): bool => $column['key'] === $this->sortField && $column['sortable']);

        $sortColumn = (string) ($column['sort_column'] ?? 'id');
        $query->orderBy($sortColumn, $direction);
    }

    protected function applySelectColumns(Builder $query): void
    {
        $columnKeys = collect($this->normalizedColumns())
            ->map(fn (array $column): string => $column['sort_column'])
            ->filter(fn (string $column): bool => ! str_contains($column, '.'))
            ->merge(['id']);

        $detailKeys = collect($this->details())
            ->map(fn (array $field): string => (string) ($field['key'] ?? ''))
            ->filter(fn (string $column): bool => $column !== '' && ! str_contains($column, '.'));

        $columns = $columnKeys
            ->merge($detailKeys)
            ->unique()
            ->values()
            ->all();

        if ($columns !== []) {
            $query->select($columns);
        }
    }

    protected function applyWith(Builder $query): void
    {
        $relations = collect($this->normalizedColumns())
            ->map(fn (array $column): string => (string) ($column['sort_column'] ?? ''))
            ->merge(collect($this->details())->map(fn (array $field): string => (string) ($field['key'] ?? '')))
            ->filter(fn (string $path): bool => str_contains($path, '.'))
            ->map(fn (string $path): string => explode('.', $path)[0])
            ->unique()
            ->values()
            ->all();

        if ($relations !== []) {
            $query->with($relations);
        }
    }

    protected function clampedPerPage(): int
    {
        return max($this->minPerPage(), min($this->maxPerPage(), $this->perPage));
    }

    protected function columnTypes(): ColumnTypeRegistry
    {
        return app(ColumnTypeRegistry::class);
    }

    protected function filterTypes(): FilterTypeRegistry
    {
        return app(FilterTypeRegistry::class);
    }
}
