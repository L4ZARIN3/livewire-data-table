<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\DataTableComponent;

final class MetaAccountSyncTable extends DataTableComponent
{
    protected function tableQuery(): Builder
    {
        return MetaAccountSync::query();
    }

    protected function columns(): array
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            [
                'key' => 'facebookAccount.facebook_id',
                'label' => 'Facebook Remote ID',
                'sortable' => false,
                'searchable' => true,
            ],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
    }

    protected function filters(): array
    {
        return [
            [
                'key' => 'facebookAccount.facebook_id',
                'type' => 'text',
                'operator' => 'like',
            ],
        ];
    }
}
