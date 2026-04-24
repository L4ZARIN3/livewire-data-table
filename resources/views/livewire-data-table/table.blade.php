@pushonce('lazarini-livewire-data-table-styles')
<style>
    @keyframes fadeSlideIn {
        from {
            opacity: 0;
            transform: translateY(6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-slide-in {
        animation: fadeSlideIn .25s ease-out both;
    }
</style>
@endpushonce

@php
$loadingTargets = 'sortBy,clearFilters,runAction,perPage,search,filterValues,previousPage,nextPage,gotoPage';
@endphp

<div class="p-6 space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h3 class="text-lg font-semibold text-slate-100 tracking-tight">{{ $title }}</h3>
            @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <span wire:loading.flex wire:target="{{ $loadingTargets }}"
                class="hidden items-center gap-1.5 text-xs text-indigo-400">
                <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Atualizando...
            </span>

            <div
                class="inline-flex items-center gap-2 rounded-full bg-slate-800/70 px-3 py-1 text-xs font-semibold text-slate-200 ring-1 ring-inset ring-slate-700">
                <span class="text-slate-400">Total</span>
                <span class="text-slate-100">{{ $rows->total() }}</span>
            </div>
        </div>
    </div>

    <div class="grid gap-3 lg:grid-cols-[auto,1fr] lg:items-end">
        <div class="grid gap-3 sm:grid-cols-[7rem,1fr,auto] sm:items-end">
            <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-300">Por pag.</label>
                <select wire:model.live="perPage"
                    class="mt-1 block h-10 w-full rounded-xl border !border-slate-800 !bg-slate-950/40 !text-slate-100 shadow-none transition-colors duration-200 focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    @foreach ($perPageOptions as $size)
                    <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300">Busca global</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Pesquisar nas colunas indexadas"
                    class="mt-1 block h-10 w-full rounded-xl border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 shadow-none transition-colors duration-200 focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
            </div>

            <button type="button" wire:click="clearFilters"
                class="group h-10 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800/60 px-4 text-sm font-semibold text-slate-100 ring-1 ring-inset ring-slate-700 transition-all duration-200 hover:bg-slate-800 hover:ring-slate-600 active:scale-[.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/60 sm:w-auto">
                <x-heroicon-o-x-mark
                    class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:rotate-90 group-hover:text-slate-200" />
                Limpar filtros
            </button>
        </div>

        @if ($actions !== [])
        <div class="flex w-full flex-wrap items-center justify-end gap-2">
            @foreach ($actions as $action)
            <button type="button" wire:click="runAction('{{ $action['key'] }}')" @disabled(!$action['enabled'])
                class="{{ $action['button_class'] !== '' ? $action['button_class'] : 'group inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-500/10 px-4 text-sm font-semibold text-indigo-300 ring-1 ring-inset ring-indigo-400/30 transition-all duration-200 hover:-translate-y-0.5 hover:bg-indigo-500/20 hover:text-indigo-200 hover:ring-indigo-300/40 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950' }}">
                @if ($action['icon'] !== '')
                <x-dynamic-component :component="$action['icon']"
                    class="{{ $action['icon_class'] !== '' ? $action['icon_class'] : 'h-4 w-4 text-indigo-300 transition-transform duration-200 group-hover:scale-110' }}" />
                @endif
                {{ $action['label'] }}
            </button>
            @endforeach
        </div>
        @endif
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/20 transition-opacity duration-200"
        wire:loading.class="opacity-50 pointer-events-none" wire:target="{{ $loadingTargets }}">
        <table class="w-full table-fixed divide-y divide-slate-800 min-w-[88rem] md:min-w-full">
            <thead class="bg-slate-900/70">
                <tr>
                    @foreach ($columns as $column)
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider {{ $column['header_class'] }}">
                        @if ($column['sortable'])
                        <button type="button" wire:click="sortBy('{{ $column['key'] }}')"
                            class="group inline-flex items-center gap-2 transition-colors duration-150 hover:text-slate-100">
                            <span>{{ $column['label'] }}</span>
                            <svg viewBox="0 0 20 20"
                                class="h-4 w-4 shrink-0 transition-all duration-200 {{ $sortField === $column['key'] ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300' }} {{ $sortField === $column['key'] && $sortDirection === 'desc' ? 'rotate-180' : '' }}"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M10 4a.75.75 0 01.53.22l4.25 4.25a.75.75 0 11-1.06 1.06L10.75 6.56v8.69a.75.75 0 01-1.5 0V6.56L6.28 9.53a.75.75 0 11-1.06-1.06l4.25-4.25A.75.75 0 0110 4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                        @else
                        <span>{{ $column['label'] }}</span>
                        @endif
                    </th>
                    @endforeach
                    @if ($details !== [])
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Det.</th>
                    @endif
                </tr>

                @if ($filters !== [])
                <tr class="bg-slate-950/40">
                    @foreach ($columns as $column)
                    @php
                    $filter = collect($filters)->firstWhere('key', $column['key']);
                    @endphp
                    <th class="px-4 py-3">
                        @if (is_array($filter))
                        @if ($filter['type'] === 'text')
                        <input type="text"
                            wire:model.live.debounce.300ms="filterValues.{{ $filter['state_key'] }}"
                            placeholder="{{ $filter['placeholder'] !== '' ? $filter['placeholder'] : 'Filtrar...' }}"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 {{ $filter['class'] }}" />
                        @elseif($filter['type'] === 'select')
                        <select wire:model.live="filterValues.{{ $filter['state_key'] }}"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 {{ $filter['class'] }}">
                            <option value="">Todos</option>
                            @foreach ($filter['options'] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                        @elseif($filter['type'] === 'date_range')
                        <div class="grid grid-cols-1 gap-2">
                            <input type="date"
                                wire:model.live="filterValues.{{ $filter['state_key'] }}.from"
                                class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 {{ $filter['class'] }}" />
                            <input type="date"
                                wire:model.live="filterValues.{{ $filter['state_key'] }}.to"
                                class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 {{ $filter['class'] }}" />
                        </div>
                        @elseif($filter['type'] === 'numeric')
                        <div class="grid grid-cols-1 gap-2">
                            <input type="number"
                                wire:model.live.debounce.300ms="filterValues.{{ $filter['state_key'] }}.min"
                                placeholder="Min"
                                class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 {{ $filter['class'] }}" />
                            <input type="number"
                                wire:model.live.debounce.300ms="filterValues.{{ $filter['state_key'] }}.max"
                                placeholder="Max"
                                class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 {{ $filter['class'] }}" />
                        </div>
                        @endif
                        @endif
                    </th>
                    @endforeach
                    @if ($details !== [])
                    <th class="px-4 py-3"></th>
                    @endif
                </tr>
                @endif
            </thead>

            <tbody class="divide-y divide-slate-800">
                @forelse ($rows as $row)
                <tr class="fade-slide-in odd:bg-slate-950/10 even:bg-slate-950/20 transition-colors duration-150 hover:bg-slate-800/40"
                    wire:key="row-{{ $row->getKey() }}">
                    @foreach ($columns as $column)
                    <td class="px-4 py-3 text-sm text-slate-200 truncate {{ $column['cell_class'] }}"
                        title="{{ $this->renderCell($row, $column) }}">
                        @if ($column['view'])
                        @include($column['view'], [
                        'row' => $row,
                        'column' => $column,
                        'value' => $this->cellValue($row, $column),
                        ])
                        @else
                        {{ $this->renderCell($row, $column) }}
                        @endif
                    </td>
                    @endforeach
                    @if ($details !== [])
                    <td class="px-4 py-3 text-sm text-slate-200">
                        <button type="button" wire:click="toggleExpandedRow({{ $row->getKey() }})"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900/70 text-slate-300 ring-1 ring-inset ring-slate-700 transition-all duration-200 hover:bg-slate-800 hover:text-slate-100 hover:ring-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/60"
                            title="{{ $expandedRowKey === (string) $row->getKey() ? 'Ocultar detalhes' : 'Ver detalhes' }}"
                            aria-expanded="{{ $expandedRowKey === (string) $row->getKey() ? 'true' : 'false' }}">
                            <svg viewBox="0 0 20 20"
                                class="h-4 w-4 transition-transform duration-200 {{ $expandedRowKey === (string) $row->getKey() ? 'rotate-180' : '' }}"
                                fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </td>
                    @endif
                </tr>

                @if ($details !== [] && $expandedRowKey === (string) $row->getKey())
                <tr wire:key="row-expanded-{{ $row->getKey() }}" class="bg-slate-900/35">
                    <td colspan="{{ count($columns) + 1 }}" class="px-4 pb-4 pt-1">
                        @include('livewire-data-table::row-details', ['row' => $row, 'details' => $details])
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="{{ count($columns) + ($details !== [] ? 1 : 0) }}" class="px-4 py-16">
                        <div class="flex flex-col items-center gap-4 text-center">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-900/60 ring-1 ring-inset ring-slate-800">
                                <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.15 6.15a7.5 7.5 0 0 0 10.5 10.5Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-200">Nenhum registro encontrado</p>
                                <p class="mt-1 text-sm text-slate-400">
                                    Ajuste os filtros ou
                                    <button type="button" wire:click="clearFilters"
                                        class="text-indigo-400 underline underline-offset-2 transition-colors duration-150 hover:text-indigo-300">
                                        limpe a busca
                                    </button>.
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-400">
            Mostrando <span class="font-semibold text-slate-200">{{ $rows->count() }}</span>
            de <span class="font-semibold text-slate-200">{{ $rows->total() }}</span>
        </p>
        <div class="text-slate-200">{{ $rows->onEachSide(1)->links(data: ['scrollTo' => false]) }}</div>
    </div>
</div>
