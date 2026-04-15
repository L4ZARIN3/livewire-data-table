<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\DataTableComponent;

final class LeadsTable extends DataTableComponent
{
    protected function tableQuery(): Builder
    {
        return Lead::query();
    }

    protected function title(): string
    {
        return 'Leads rastreados';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'phone', 'label' => 'Telefone', 'sortable' => true, 'searchable' => true],
            ['key' => 'wpp_name', 'label' => 'Nome WhatsApp', 'sortable' => true, 'searchable' => true],
            ['key' => 'campaign_name', 'label' => 'Campanha', 'sortable' => true, 'searchable' => true],
            [
                'key' => 'source_app',
                'label' => 'Plataforma',
                'sortable' => true,
                'type' => 'badge',
                'options' => ['ig' => 'Instagram', 'fb' => 'Facebook'],
            ],
            ['key' => 'created_at', 'label' => 'Criado em', 'sortable' => true, 'type' => 'datetime'],
        ];
    }

    protected function filters(): array
    {
        return [
            ['key' => 'phone', 'type' => 'text', 'operator' => 'like'],
            ['key' => 'source_app', 'type' => 'select', 'options' => ['ig' => 'Instagram', 'fb' => 'Facebook']],
            ['key' => 'created_at', 'type' => 'date_range', 'column' => 'created_at'],
        ];
    }

    protected function details(): array
    {
        return [
            ['key' => 'phone', 'label' => 'Telefone'],
            ['key' => 'wpp_name', 'label' => 'Nome'],
            ['key' => 'campaign_name', 'label' => 'Campanha', 'wrapper_class' => 'md:col-span-2'],
        ];
    }
}
