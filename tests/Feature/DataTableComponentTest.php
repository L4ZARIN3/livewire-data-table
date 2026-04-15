<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Tests\Feature;

use Carbon\Carbon;
use Lazarini\LivewireDataTable\Tests\Fixtures\Lead;
use Lazarini\LivewireDataTable\Tests\Fixtures\LeadsTable;
use Lazarini\LivewireDataTable\Tests\TestCase;

final class DataTableComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Lead::query()->create([
            'phone' => '11911111111',
            'wpp_name' => 'Ana',
            'campaign_name' => 'Campanha A',
            'source_app' => 'ig',
            'created_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);

        Lead::query()->create([
            'phone' => '11922222222',
            'wpp_name' => 'Bruno',
            'campaign_name' => 'Campanha B',
            'source_app' => 'fb',
            'created_at' => Carbon::parse('2026-02-01 10:00:00'),
        ]);

        Lead::query()->create([
            'phone' => '11933333333',
            'wpp_name' => 'Carla',
            'campaign_name' => 'Campanha C',
            'source_app' => 'ig',
            'created_at' => Carbon::parse('2026-03-01 10:00:00'),
        ]);
    }

    public function test_it_loads_paginated_rows(): void
    {
        $component = app(LeadsTable::class);
        $component->mount();

        $rows = $component->rows();

        $this->assertSame(3, $rows->total());
        $this->assertSame(3, $rows->count());
    }

    public function test_it_filters_by_text(): void
    {
        $component = app(LeadsTable::class);
        $component->mount();
        $component->filterValues['phone'] = '3333';

        $rows = $component->rows();

        $this->assertSame(1, $rows->total());
        $this->assertSame('Carla', $rows->first()->wpp_name);
    }

    public function test_it_filters_by_select_and_date_range(): void
    {
        $component = app(LeadsTable::class);
        $component->mount();
        $component->filterValues['source_app'] = 'fb';

        $rowsBySource = $component->rows();

        $this->assertSame(1, $rowsBySource->total());
        $this->assertSame('Bruno', $rowsBySource->first()->wpp_name);

        $component = app(LeadsTable::class);
        $component->mount();
        $component->filterValues['created_at'] = ['from' => '2026-02-01', 'to' => '2026-02-28'];

        $rowsByDate = $component->rows();

        $this->assertSame(1, $rowsByDate->total());
        $this->assertSame('Bruno', $rowsByDate->first()->wpp_name);
    }

    public function test_it_supports_global_search_and_sorting(): void
    {
        $component = app(LeadsTable::class);
        $component->mount();
        $component->search = 'Campanha B';

        $rowsBySearch = $component->rows();
        $this->assertSame(1, $rowsBySearch->total());
        $this->assertSame('Bruno', $rowsBySearch->first()->wpp_name);

        $component = app(LeadsTable::class);
        $component->mount();
        $component->sortBy('wpp_name');
        $this->assertSame('asc', $component->sortDirection);
        $component->sortBy('wpp_name');
        $this->assertSame('desc', $component->sortDirection);
    }
}
