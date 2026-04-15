<div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($details as $detail)
        <div
            class="rounded-xl border border-slate-800 bg-slate-950/50 p-3 {{ (string) ($detail['wrapper_class'] ?? '') }}">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {{ (string) ($detail['label'] ?? $detail['key']) }}
            </p>
            <p class="mt-1 text-sm text-slate-200 break-all">
                @php
                    $value = data_get($row, (string) ($detail['key'] ?? ''));
                    if (is_callable($detail['format'] ?? null)) {
                        $value = $detail['format']($value, $row, $detail);
                    }
                @endphp
                {{ $value === null || $value === '' ? ((string) ($detail['placeholder'] ?? '—')) : $value }}
            </p>
        </div>
    @endforeach
</div>
