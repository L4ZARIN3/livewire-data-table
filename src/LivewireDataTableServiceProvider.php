<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable;

use Illuminate\Support\ServiceProvider;
use Lazarini\LivewireDataTable\Support\ColumnTypeRegistry;
use Lazarini\LivewireDataTable\Support\FilterTypeRegistry;

class LivewireDataTableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/livewire-data-table.php', 'livewire-data-table');

        $this->app->singleton(ColumnTypeRegistry::class, function () {
            $registry = new ColumnTypeRegistry();

            foreach (config('livewire-data-table.column_types', []) as $key => $typeClass) {
                if (is_string($typeClass) && class_exists($typeClass)) {
                    $registry->register((string) $key, app($typeClass));
                }
            }

            return $registry;
        });

        $this->app->singleton(FilterTypeRegistry::class, function () {
            $registry = new FilterTypeRegistry();

            foreach (config('livewire-data-table.filter_types', []) as $key => $typeClass) {
                if (is_string($typeClass) && class_exists($typeClass)) {
                    $registry->register((string) $key, app($typeClass));
                }
            }

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views/livewire-data-table', 'livewire-data-table');

        $this->publishes([
            __DIR__ . '/../config/livewire-data-table.php' => config_path('livewire-data-table.php'),
        ], 'livewire-data-table-config');

        $this->publishes([
            __DIR__ . '/../resources/views/livewire-data-table' => resource_path('views/vendor/livewire-data-table'),
        ], 'livewire-data-table-views');
    }
}
