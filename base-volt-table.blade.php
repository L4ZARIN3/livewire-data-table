<?php

use App\Models\FacebookAdAccount;
use App\Models\MetaWhatsappTracking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

new class extends Component
{
    use WithPagination;

    #[Locked]
    public string $localAdAccountId;

    #[Locked]
    public string $remoteAdAccountId;

    #[Locked]
    public string $adAccountName;

    public string $filterPhone = '';

    public string $filterName = '';

    public string $filterAd = '';

    public string $filterAdset = '';

    public string $filterCampaign = '';

    public string $filterCreativeLink = '';

    public string $filterSourceApp = '';

    public string $filterCreatedAtFrom = '';

    public string $filterCreatedAtTo = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public int $perPage = 10;

    public ?int $expandedLeadId = null;

    private const SORT_COLUMN_MAP = [
        'phone' => 'phone',
        'wpp_name' => 'wpp_name',
        'ad_name' => 'ad_name',
        'adset_name' => 'adset_name',
        'campaign_name' => 'campaign_name',
        'creative_link_url' => 'creative_link_url',
        'source_app' => 'source_app',
        'created_at' => 'created_at',
    ];

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    private const PER_PAGE_MIN = 5;

    private const PER_PAGE_MAX = 100;

    public function mount(string $localAdAccountId): void
    {
        $this->localAdAccountId = $localAdAccountId;

        $account = FacebookAdAccount::query()
            ->where('user_id', Auth::id())
            ->where('id', $this->localAdAccountId)
            ->first(['remote_ad_account_id', 'name']);

        $this->remoteAdAccountId = $account?->remote_ad_account_id ?? '';
        $this->adAccountName = $account?->name ?? '';
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'filter') || $property === 'perPage') {
            $this->expandedLeadId = null;
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['filterPhone', 'filterName', 'filterAd', 'filterAdset', 'filterCampaign', 'filterMediaType', 'filterSourceApp', 'filterCreatedAtFrom', 'filterCreatedAtTo', 'perPage']);
        $this->expandedLeadId = null;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, self::SORT_COLUMN_MAP)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->expandedLeadId = null;
        $this->resetPage();
    }

    public function toggleExpandedLead(int $leadId): void
    {
        $this->expandedLeadId = $this->expandedLeadId === $leadId ? null : $leadId;
    }

    public function with(): array
    {
        return [
            'leads' => $this->queryLeads(),
        ];
    }

    public function downloadCsv()
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = 'facebook_whatsapp_leads_' . $this->remoteAdAccountId . '_' . $timestamp . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['ID', 'Telefone', 'Nome WhatsApp', 'ID Anúncio', 'Anúncio', 'ID Conjunto', 'Conjunto', 'ID Campanha', 'Campanha', 'Tipo de mídia', 'Source app', 'Criativo', 'Criado em'], ';');

            MetaWhatsappTracking::query()
                ->where('user_id', Auth::id())
                ->where('remote_ad_account_id', $this->remoteAdAccountId)
                ->select(['id', 'phone', 'wpp_name', 'ad_id', 'ad_name', 'adset_id', 'remote_ad_account_id', 'adset_name', 'campaign_id', 'campaign_name', 'media_type', 'source_app', 'creative_body', 'created_at'])
                ->orderBy('id')  // importante pro chunk
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [$row->id, $this->safe($row->phone), $this->safe($row->wpp_name), $this->safe($row->ad_id), $this->safe($row->ad_name), $this->safe($row->adset_id), $this->safe($row->adset_name), $this->safe($row->campaign_id), $this->safe($row->campaign_name), $this->safe($this->mediaTypeLabel($row->media_type)), $this->safe($row->source_app), $this->safe($row->creative_body), optional($row->created_at)->format('Y-m-d H:i:s')], ';');
                    }
                });

            fclose($handle);
        }, $filename);
    }

    public function downloadJson()
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = 'facebook_whatsapp_leads_' . $this->remoteAdAccountId . '_' . $timestamp . '.json';

        return new StreamedJsonResponse(
            $this->generateLeads(),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ],
        );
    }

    private function generateLeads(): Generator
    {
        $rows = MetaWhatsappTracking::query()
            ->where('user_id', Auth::id())
            ->where('remote_ad_account_id', $this->remoteAdAccountId)
            ->select(['id', 'phone', 'wpp_name', 'ad_id', 'ad_name', 'adset_id', 'adset_name', 'remote_ad_account_id', 'campaign_id', 'campaign_name', 'media_type', 'source_app', 'creative_body', 'created_at'])
            ->orderBy('id')
            ->lazy(500);

        foreach ($rows as $row) {
            yield [
                'id' => $row->id,
                'phone' => $row->phone,
                'wpp_name' => $row->wpp_name,
                'ad_id' => $row->ad_id,
                'ad_name' => $row->ad_name,
                'adset_id' => $row->adset_id,
                'adset_name' => $row->adset_name,
                'campaign_id' => $row->campaign_id,
                'campaign_name' => $row->campaign_name,
                'media_type' => $row->media_type,
                'source_app' => $row->source_app,
                'creative_body' => $row->creative_body,
                'created_at' => $row->created_at?->format('Y-m-d H:i:s'),
            ];
        }
    }

    private function mediaTypeLabel(?int $mediaType): string
    {
        return match ($mediaType) {
            0 => 'Texto',
            1 => 'Imagem',
            2 => 'Vídeo',
            3 => 'Áudio',
            4 => 'Documento',
            default => 'Outro',
        };
    }

    protected function safe($value)
    {
        if (is_null($value)) {
            return '';
        }

        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    private function queryLeads(): LengthAwarePaginator
    {
        $query = MetaWhatsappTracking::query()
            ->where('user_id', Auth::id())
            ->where('remote_ad_account_id', $this->remoteAdAccountId)
            ->select([
                'id',
                'source_id',
                'phone',
                'wpp_name',
                'phone_receiver',
                'receiver_push_name',
                'ad_id',
                'ad_name',
                'adset_id',
                'adset_name',
                'campaign_id',
                'campaign_name',
                'creative_title',
                'creative_link_url',
                'creative_body',
                'creative_call_to_action',
                'media_type',
                'source_app',
                'instance_name',
                'ctwa_clid',
                'conversion_domain',
                'ad_created_at',
                'lead_captured_at',
                'created_at',
            ]);

        $this->applyFilters($query);
        $this->applySorting($query);

        return $query->paginate($this->clampedPerPage());
    }

    private function applyFilters(Builder $query): void
    {
        $phone = trim($this->filterPhone);
        $name = trim($this->filterName);
        $ad = trim($this->filterAd);
        $adset = trim($this->filterAdset);
        $campaign = trim($this->filterCampaign);
        $creativeLink = trim($this->filterCreativeLink);
        $sourceApp = trim($this->filterSourceApp);
        $createdAtFrom = trim($this->filterCreatedAtFrom);
        $createdAtTo = trim($this->filterCreatedAtTo);

        if ($phone !== '') {
            $query->where('phone', 'like', "%{$phone}%");
        }

        if ($name !== '') {
            $query->where('wpp_name', 'like', "%{$name}%");
        }

        if ($ad !== '') {
            $query->where('ad_name', 'like', "%{$ad}%");
        }

        if ($adset !== '') {
            $query->where('adset_name', 'like', "%{$adset}%");
        }

        if ($campaign !== '') {
            $query->where('campaign_name', 'like', "%{$campaign}%");
        }

        if ($creativeLink !== '') {
            $query->where('creative_link_url', 'like', "%{$creativeLink}%");
        }

        if ($sourceApp !== '') {
            $query->where('source_app', 'like', "%{$sourceApp}%");
        }

        if ($createdAtFrom !== '' && $createdAtTo !== '' && $createdAtFrom > $createdAtTo) {
            [$createdAtFrom, $createdAtTo] = [$createdAtTo, $createdAtFrom];
        }

        if ($createdAtFrom !== '') {
            $query->whereDate('created_at', '>=', $createdAtFrom);
        }

        if ($createdAtTo !== '') {
            $query->whereDate('created_at', '<=', $createdAtTo);
        }
    }

    private function applySorting(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $column = self::SORT_COLUMN_MAP[$this->sortField] ?? 'created_at';

        $query->orderBy($column, $direction);
    }

    private function clampedPerPage(): int
    {
        return max(self::PER_PAGE_MIN, min(self::PER_PAGE_MAX, $this->perPage));
    }
};
?>

@push('styles')
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

    .fade-slide-in:nth-child(1) {
        animation-delay: 0ms;
    }

    .fade-slide-in:nth-child(2) {
        animation-delay: 30ms;
    }

    .fade-slide-in:nth-child(3) {
        animation-delay: 60ms;
    }

    .fade-slide-in:nth-child(4) {
        animation-delay: 90ms;
    }

    .fade-slide-in:nth-child(5) {
        animation-delay: 120ms;
    }
</style>
@endpush

@php
$loadingTargets =
'sortBy,clearFilters,perPage,filterPhone,filterName,filterAd,filterAdset,filterCampaign,filterMediaType,filterSourceApp,filterCreatedAtFrom,filterCreatedAtTo,previousPage,nextPage,gotoPage';
@endphp

<div class="p-6 space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h3 class="text-lg font-semibold text-slate-100 tracking-tight">
                Leads Rastreados
            </h3>
            <p class="mt-0.5 text-sm text-slate-400">
                Conta de anúncio: {{ $this->adAccountName }} - {{ $this->remoteAdAccountId }}
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <span wire:loading.flex wire:target="{{ $loadingTargets }}"
                class="hidden items-center gap-1.5 text-xs text-indigo-400">
                <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Atualizando…
            </span>

            <div
                class="inline-flex items-center gap-2 rounded-full bg-slate-800/70 px-3 py-1 text-xs font-semibold text-slate-200 ring-1 ring-inset ring-slate-700">
                <span class="text-slate-400">Total</span>
                <span class="text-slate-100">{{ $leads->total() }}</span>
            </div>
        </div>
    </div>

    <div class="grid gap-3 lg:grid-cols-[auto,1fr] lg:items-end">
        <div class="grid gap-3 sm:grid-cols-[7rem,auto] sm:items-end">
            <div class="min-w-0">
                <label for="leads-per-page" class="block text-xs font-semibold text-slate-300">
                    Por pág.
                </label>
                <select id="leads-per-page" wire:model.live="perPage"
                    class="mt-1 block h-10 w-full rounded-xl border !border-slate-800 !bg-slate-950/40 !text-slate-100 shadow-none transition-colors duration-200 focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                    @foreach (self::PER_PAGE_OPTIONS as $size)
                    <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" wire:click="clearFilters"
                class="group h-10 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800/60 px-4 text-sm font-semibold text-slate-100 ring-1 ring-inset ring-slate-700 transition-all duration-200 hover:bg-slate-800 hover:ring-slate-600 active:scale-[.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/60 sm:w-auto">
                <x-heroicon-o-x-mark
                    class="h-4 w-4 text-slate-400 transition-transform duration-200 group-hover:rotate-90 group-hover:text-slate-200" />
                Limpar filtros
            </button>
        </div>

        <div class="flex w-full flex-wrap items-center justify-end gap-2">
            <button type="button" wire:click="downloadCsv"
                class="group inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-emerald-500/10 px-4 text-sm font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-400/30 transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-500/20 hover:text-emerald-200 hover:ring-emerald-300/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">
                <x-heroicon-o-arrow-down-tray
                    class="h-4 w-4 text-emerald-300 transition-transform duration-200 group-hover:translate-y-0.5" />
                Baixar CSV
            </button>

            <button type="button" wire:click="downloadJson"
                class="group inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-500/10 px-4 text-sm font-semibold text-indigo-300 ring-1 ring-inset ring-indigo-400/30 transition-all duration-200 hover:-translate-y-0.5 hover:bg-indigo-500/20 hover:text-indigo-200 hover:ring-indigo-300/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">
                <x-heroicon-o-code-bracket
                    class="h-4 w-4 text-indigo-300 transition-transform duration-200 group-hover:scale-110" />
                Baixar JSON
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/20 transition-opacity duration-200"
        wire:loading.class="opacity-50 pointer-events-none" wire:target="{{ $loadingTargets }}">
        <table class="w-full table-fixed divide-y divide-slate-800 min-w-[88rem] md:min-w-full">
            <caption class="sr-only">Tabela de leads rastreados por conta de anúncio.</caption>
            <colgroup>
                <col class="w-[12%]" />
                <col class="w-[14%]" />
                <col class="w-[16%]" />
                <col class="w-[15%]" />
                <col class="w-[15%]" />
                <col class="w-[10%]" />
                <col class="w-[8%]" />
                <col class="w-[6%]" />
                <col class="w-[4%]" />
            </colgroup>
            <thead class="bg-slate-900/70">
                <tr>
                    @foreach ([
                    'phone' => 'Telefone',
                    'wpp_name' => 'Nome WhatsApp',
                    'campaign_name' => 'Campanha',
                    'adset_name' => 'Conjunto',
                    'ad_name' => 'Anúncio',
                    'media_type' => 'Mídia',
                    'source_app' => 'Plataforma',
                    'created_at' => 'Criado em',
                    ] as $field => $label)
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                        <button type="button" wire:click="sortBy('{{ $field }}')"
                            class="group inline-flex items-center gap-2 transition-colors duration-150 hover:text-slate-100">
                            <span>{{ $label }}</span>
                            <x-heroicon-m-chevron-up
                                class="h-4 w-4 shrink-0 transition-all duration-200 {{ $sortField === $field ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300' }} {{ $sortField === $field && $sortDirection === 'desc' ? 'rotate-180' : '' }}" />
                        </button>
                    </th>
                    @endforeach
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">
                        Det.
                    </th>
                </tr>

                <tr class="bg-slate-950/40" wire:key="leads-filter-row">
                    <th class="px-4 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filterPhone"
                            placeholder="Filtrar telefone"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </th>
                    <th class="px-4 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filterName" placeholder="Filtrar nome"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </th>
                    <th class="px-4 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filterCampaign"
                            placeholder="Filtrar campanha"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </th>
                    <th class="px-4 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filterAdset"
                            placeholder="Filtrar conjunto"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </th>
                    <th class="px-4 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filterAd" placeholder="Filtrar anúncio"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </th>
                    <th class="px-4 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filterCreativeLink" placeholder="Filtrar Link"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </th>
                    <!-- <th class="px-4 py-3">
                        <select wire:model.live="filterMediaType"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30">
                            <option value="">Todas</option>
                            <option value="0">Texto</option>
                            <option value="1">Imagem</option>
                            <option value="2">Vídeo</option>
                            <option value="3">Áudio</option>
                            <option value="4">Documento</option>
                        </select>
                    </th> -->
                    <th class="px-4 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filterSourceApp"
                            placeholder="Ex.: Instagram"
                            class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 !placeholder:text-slate-500 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                    </th>
                    <th class="px-4 py-3">
                        <div class="grid grid-cols-1 gap-2">
                            <input type="date" wire:model.live="filterCreatedAtFrom"
                                class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                            <input type="date" wire:model.live="filterCreatedAtTo"
                                class="block w-full rounded-lg border !border-slate-800 !bg-slate-950/40 !text-slate-100 text-xs shadow-none focus:!border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                        </div>
                    </th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800">
                @forelse ($leads as $lead)
                <tr class="fade-slide-in odd:bg-slate-950/10 even:bg-slate-950/20 transition-colors duration-150 hover:bg-slate-800/40"
                    wire:key="lead-{{ $lead->id }}">
                    <td class="px-4 py-3 text-sm text-slate-200 truncate" title="{{ $lead->phone }}">
                        {{ $lead->phone }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-200 truncate" title="{{ $lead->wpp_name }}">
                        {{ $lead->wpp_name ?: 'Sem nome' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-300 truncate" title="{{ $lead->campaign_name }}">
                        {{ $lead->campaign_name ?: 'Sem campanha' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-300 truncate" title="{{ $lead->adset_name }}">
                        {{ $lead->adset_name ?: 'Sem conjunto' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-200 truncate" title="{{ $lead->ad_name }}">
                        {{ $lead->ad_name ?: 'Sem anúncio' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-200 truncate" title="{{ $lead->creative_link_url }}">
                        {{ $lead->creative_link_url ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-300 truncate" title="{{ $lead->source_app }}">
                        {{ $lead->source_app ?: '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-400 whitespace-nowrap">
                        {{ $lead->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-200">
                        <button type="button" wire:click="toggleExpandedLead({{ $lead->id }})"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900/70 text-slate-300 ring-1 ring-inset ring-slate-700 transition-all duration-200 hover:bg-slate-800 hover:text-slate-100 hover:ring-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/60"
                            title="{{ $expandedLeadId === $lead->id ? 'Ocultar detalhes' : 'Ver detalhes' }}"
                            aria-expanded="{{ $expandedLeadId === $lead->id ? 'true' : 'false' }}">
                            <x-heroicon-o-chevron-down
                                class="h-4 w-4 transition-transform duration-200 {{ $expandedLeadId === $lead->id ? 'rotate-180' : '' }}" />
                        </button>
                    </td>
                </tr>
                @if ($expandedLeadId === $lead->id)
                <tr wire:key="lead-expanded-{{ $lead->id }}" class="bg-slate-900/35">
                    <td colspan="9" class="px-4 pb-4 pt-1">
                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">ID Origem</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->source_id ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">ID Anúncio</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->ad_id ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">ID Conjunto</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->adset_id ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">ID Campanha</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->campaign_id ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Recebedor WhatsApp</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->phone_receiver ?: '—' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $lead->receiver_push_name ?: 'Sem nome do recebedor' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Instância</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->instance_name ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">CTWA CLID</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->ctwa_clid ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Domínio de Conversão</p>
                                <p class="mt-1 text-sm text-slate-200 break-all">{{ $lead->conversion_domain ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">CTA Criativo</p>
                                <p class="mt-1 text-sm text-slate-200">{{ $lead->creative_call_to_action ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 md:col-span-2 xl:col-span-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Título Criativo</p>
                                <p class="mt-1 text-sm text-slate-200 break-words">{{ $lead->creative_title ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 md:col-span-2 xl:col-span-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Link Criativo</p>
                                <p class="mt-1 text-sm text-slate-200 break-words">{{ $lead->creative_link_url ?: '—' }} - <a href="{{ $lead->creative_link_url }}" target="_blank" rel="noopener noreferrer" class="text-sky-300 underline decoration-sky-300/70 underline-offset-2 hover:text-sky-200">aqui</a></p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 md:col-span-2 xl:col-span-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Texto Criativo</p>
                                <p class="mt-1 text-sm text-slate-200 break-words">{{ $lead->creative_body ?: '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Ad Criado em</p>
                                <p class="mt-1 text-sm text-slate-200">{{ $lead->ad_created_at?->format('d/m/Y H:i:s') ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Lead Capturado em</p>
                                <p class="mt-1 text-sm text-slate-200">{{ $lead->lead_captured_at?->format('d/m/Y H:i:s') ?? '—' }}</p>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-16">
                        <div class="flex flex-col items-center gap-4 text-center">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-900/60 ring-1 ring-inset ring-slate-800">
                                <x-heroicon-o-magnifying-glass class="h-7 w-7 text-slate-400" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-200">Nenhum lead encontrado</p>
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
            Mostrando <span class="font-semibold text-slate-200">{{ $leads->count() }}</span>
            de <span class="font-semibold text-slate-200">{{ $leads->total() }}</span>
        </p>
        <div class="text-slate-200">
            {{ $leads->onEachSide(1)->links('vendor.livewire.compact-pagination') }}
        </div>
    </div>

</div>