# lazarini/livewire-data-table

Pacote reutilizavel para Laravel + Livewire 4 com API declarativa por arrays para montar tabelas dinamicas com:

- Colunas configuraveis (tipos, formatacao, busca e ordenacao)
- Filtros de texto, select, intervalo de datas e numerico
- Paginacao, busca em tempo real e ordenacao
- Linha expansivel com isolamento via Livewire 4 (`#[Isolate]`)
- Arquitetura extensivel para novos tipos de coluna e filtro

## Requisitos

- PHP `^8.2`
- Laravel `^11 || ^12`
- Livewire `^4.0` (compativel tambem com `^3.5`)

## Instalacao

```bash
composer require lazarini/livewire-data-table
```

Opcionalmente publique configuracao e views:

```bash
php artisan vendor:publish --tag=livewire-data-table-config
php artisan vendor:publish --tag=livewire-data-table-views
```

Se o projeto destino usa Tailwind, inclua as views do pacote no `content` para garantir classes como `group-hover:rotate-90` no build final:

```js
content: [
  "./resources/**/*.blade.php",
  "./resources/**/*.js",
  "./vendor/lazarini/livewire-data-table/resources/views/**/*.blade.php",
]
```

## Uso Rapido

Crie um componente Livewire que estende `Lazarini\LivewireDataTable\DataTableComponent`:

```php
<?php

namespace App\Livewire\Tables;

use App\Models\MetaWhatsappTracking;
use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\DataTableComponent;

class LeadsTable extends DataTableComponent
{
    protected function tableQuery(): Builder
    {
        return MetaWhatsappTracking::query()->where('user_id', auth()->id());
    }

    protected function title(): string
    {
        return 'Leads Rastreados';
    }

    protected function subtitle(): ?string
    {
        return 'Conta de anuncio conectada';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'phone', 'label' => 'Telefone', 'sortable' => true, 'searchable' => true],
            ['key' => 'wpp_name', 'label' => 'Nome WhatsApp', 'sortable' => true, 'searchable' => true],
            ['key' => 'campaign_name', 'label' => 'Campanha', 'sortable' => true, 'searchable' => true],
            ['key' => 'adset_name', 'label' => 'Conjunto', 'sortable' => true, 'searchable' => true],
            ['key' => 'ad_name', 'label' => 'Anuncio', 'sortable' => true, 'searchable' => true],
            [
                'key' => 'source_app',
                'label' => 'Plataforma',
                'type' => 'badge',
                'sortable' => true,
                'options' => ['ig' => 'Instagram', 'fb' => 'Facebook'],
            ],
            ['key' => 'created_at', 'label' => 'Criado em', 'type' => 'datetime', 'sortable' => true, 'format' => 'd/m/Y H:i'],
        ];
    }

    protected function filters(): array
    {
        return [
            ['key' => 'phone', 'type' => 'text', 'placeholder' => 'Filtrar telefone'],
            ['key' => 'wpp_name', 'type' => 'text', 'placeholder' => 'Filtrar nome'],
            ['key' => 'source_app', 'type' => 'select', 'options' => ['ig' => 'Instagram', 'fb' => 'Facebook']],
            ['key' => 'created_at', 'type' => 'date_range'],
        ];
    }

    protected function details(): array
    {
        return [
            ['key' => 'source_id', 'label' => 'ID Origem'],
            ['key' => 'ad_id', 'label' => 'ID Anuncio'],
            ['key' => 'adset_id', 'label' => 'ID Conjunto'],
            ['key' => 'campaign_id', 'label' => 'ID Campanha'],
            ['key' => 'creative_body', 'label' => 'Texto Criativo', 'wrapper_class' => 'md:col-span-2 xl:col-span-3'],
        ];
    }
}
```

Renderize normalmente:

```blade
<livewire:tables.leads-table />
```

## Acoes Customizadas

Voce pode renderizar botoes de acao no topo da tabela sem acoplar regra de negocio no pacote. O pacote apenas desenha os botoes e chama metodos do seu componente:

```php
protected function actions(): array
{
    return [
        [
            'key' => 'csv',
            'label' => 'Baixar CSV',
            'method' => 'downloadCsv',
            'icon' => 'heroicon-o-arrow-down-tray',
            'button_class' => 'group inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-emerald-500/10 px-4 text-sm font-semibold text-emerald-300 ring-1 ring-inset ring-emerald-400/30 transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-500/20 hover:text-emerald-200 hover:ring-emerald-300/40',
            'icon_class' => 'h-4 w-4 text-emerald-300 transition-transform duration-200 group-hover:translate-y-0.5',
        ],
        [
            'key' => 'sync',
            'label' => 'Sincronizar',
            'method' => 'syncNow',
            'icon' => 'heroicon-o-arrow-path',
            'visible' => fn () => auth()->user()?->can('sync-leads') ?? false,
        ],
    ];
}

public function downloadCsv()
{
    // Sua logica aqui (download, dispatch de job, etc)
}

public function syncNow(): void
{
    // Sua logica aqui
}
```

Campos suportados em cada acao:

- `key` (obrigatorio): identificador unico da acao
- `label`: texto do botao
- `method` (obrigatorio): metodo publico do proprio componente Livewire
- `icon`: nome do componente Blade (ex.: `heroicon-o-arrow-down-tray`)
- `button_class`: classes CSS do botao
- `icon_class`: classes CSS do icone
- `enabled`: habilita/desabilita o botao (default `true`)
- `visible`: bool ou closure para decidir se a acao aparece

## API Declarativa

### Colunas

Cada item de `columns()` aceita:

- `key` (obrigatorio): campo no model, ex `phone`
- `label`: titulo exibido na tabela
- `type`: `text`, `datetime`, `badge` ou tipo custom
- `sortable`: habilita ordenacao por coluna
- `sort_column`: coluna SQL para ordenacao (quando diferente de `key`)
- `searchable`: inclui na busca global
- `format`: closure para formatacao custom
- `value`: closure para extrair valor custom
- `view`: partial blade custom para celula
- `placeholder`: valor padrao quando nulo/vazio

### Filtros

Cada item de `filters()` aceita:

- `key` (obrigatorio): identificador do filtro
- `type`: `text`, `select`, `date_range`, `numeric` ou tipo custom
- `column`: coluna SQL para aplicar filtro
- `operator`: operador (ex `=`, `>=`, `like`)
- `options`: opcoes para `select`
- `placeholder`: placeholder para campos de texto
- `apply`: closure custom para filtros complexos

## Extensao

Registre tipos novos em `config/livewire-data-table.php`:

```php
return [
    'column_types' => [
        'money' => \App\Tables\Columns\MoneyColumnType::class,
    ],
    'filter_types' => [
        'uuid' => \App\Tables\Filters\UuidFilterType::class,
    ],
];
```

As classes devem implementar:

- `Lazarini\LivewireDataTable\Contracts\ColumnTypeContract`
- `Lazarini\LivewireDataTable\Contracts\FilterTypeContract`

## Islands (Livewire 4)

O componente base usa `#[Isolate]`, reduzindo impacto de re-render em paginas com varios componentes Livewire ativos.

## Testes

```bash
composer test
```

## Exemplo completo

Veja `docs/livewire4-whatsapp-leads-example.md` para um exemplo proximo da sua base original.
