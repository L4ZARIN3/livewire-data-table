# Exemplo Livewire 4 - WhatsApp Leads

Este exemplo mostra como transformar a base `base-volt-table.blade.php` em um componente reutilizavel usando `lazarini/livewire-data-table`.

## 1. Componente

```php
<?php

namespace App\Livewire\Tables;

use App\Models\FacebookAdAccount;
use App\Models\MetaWhatsappTracking;
use Illuminate\Database\Eloquent\Builder;
use Lazarini\LivewireDataTable\DataTableComponent;
use Livewire\Attributes\Locked;

class FacebookWhatsappLeadsTable extends DataTableComponent
{
    #[Locked]
    public string $localAdAccountId;

    #[Locked]
    public string $remoteAdAccountId;

    #[Locked]
    public string $adAccountName;

    public function mount(string $localAdAccountId): void
    {
        parent::mount();

        $this->localAdAccountId = $localAdAccountId;
        $account = FacebookAdAccount::query()
            ->where('user_id', auth()->id())
            ->find($localAdAccountId, ['remote_ad_account_id', 'name']);

        $this->remoteAdAccountId = (string) ($account?->remote_ad_account_id ?? '');
        $this->adAccountName = (string) ($account?->name ?? '');
    }

    protected function tableQuery(): Builder
    {
        return MetaWhatsappTracking::query()
            ->where('user_id', auth()->id())
            ->where('remote_ad_account_id', $this->remoteAdAccountId);
    }

    protected function title(): string
    {
        return 'Leads Rastreados';
    }

    protected function subtitle(): ?string
    {
        return 'Conta: ' . $this->adAccountName . ' - ' . $this->remoteAdAccountId;
    }

    protected function columns(): array
    {
        return [
            ['key' => 'phone', 'label' => 'Telefone', 'sortable' => true, 'searchable' => true],
            ['key' => 'wpp_name', 'label' => 'Nome WhatsApp', 'sortable' => true, 'searchable' => true],
            ['key' => 'campaign_name', 'label' => 'Campanha', 'sortable' => true, 'searchable' => true],
            ['key' => 'adset_name', 'label' => 'Conjunto', 'sortable' => true, 'searchable' => true],
            ['key' => 'ad_name', 'label' => 'Anuncio', 'sortable' => true, 'searchable' => true],
            ['key' => 'creative_link_url', 'label' => 'Link', 'sortable' => true, 'searchable' => true],
            ['key' => 'source_app', 'label' => 'Plataforma', 'sortable' => true, 'searchable' => true],
            ['key' => 'created_at', 'label' => 'Criado em', 'type' => 'datetime', 'sortable' => true],
        ];
    }

    protected function filters(): array
    {
        return [
            ['key' => 'phone', 'type' => 'text', 'placeholder' => 'Filtrar telefone'],
            ['key' => 'wpp_name', 'type' => 'text', 'placeholder' => 'Filtrar nome'],
            ['key' => 'campaign_name', 'type' => 'text', 'placeholder' => 'Filtrar campanha'],
            ['key' => 'adset_name', 'type' => 'text', 'placeholder' => 'Filtrar conjunto'],
            ['key' => 'ad_name', 'type' => 'text', 'placeholder' => 'Filtrar anuncio'],
            ['key' => 'source_app', 'type' => 'text', 'placeholder' => 'Ex.: Instagram'],
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
            ['key' => 'phone_receiver', 'label' => 'Recebedor WhatsApp'],
            ['key' => 'receiver_push_name', 'label' => 'Push Name'],
            ['key' => 'instance_name', 'label' => 'Instancia'],
            ['key' => 'ctwa_clid', 'label' => 'CTWA CLID'],
            ['key' => 'conversion_domain', 'label' => 'Dominio de Conversao'],
            ['key' => 'creative_call_to_action', 'label' => 'CTA Criativo'],
            ['key' => 'creative_title', 'label' => 'Titulo Criativo', 'wrapper_class' => 'md:col-span-2 xl:col-span-3'],
            ['key' => 'creative_link_url', 'label' => 'Link Criativo', 'wrapper_class' => 'md:col-span-2 xl:col-span-3'],
            ['key' => 'creative_body', 'label' => 'Texto Criativo', 'wrapper_class' => 'md:col-span-2 xl:col-span-3'],
        ];
    }
}
```

## 2. Renderizacao na view

```blade
<livewire:tables.facebook-whatsapp-leads-table :local-ad-account-id="$accountId" />
```

## 3. Exportacoes (CSV/JSON)

Mantenha exportacoes no proprio componente concreto com metodos customizados (`downloadCsv`, `downloadJson`) para nao acoplar regras de negocio no nucleo do pacote.
