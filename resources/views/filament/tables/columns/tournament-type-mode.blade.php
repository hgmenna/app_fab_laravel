@php
    $record = $getRecord();
    $mode = $record->type?->participation_mode === 'pairs' ? 'Parejas' : 'Individual';
@endphp

<div class="flex flex-col items-start gap-1 text-xs leading-tight">
    <div class="flex items-center gap-1.5" title="Tipo de torneo">
        <x-filament::icon icon="heroicon-m-tag" class="h-3.5 w-3.5 shrink-0 text-primary-500" />
        <span class="font-medium">{{ $record->type?->code ?? '—' }}</span>
    </div>

    <div class="flex items-center gap-1.5" title="Modalidad">
        <x-filament::icon icon="heroicon-m-user-group" class="h-3.5 w-3.5 shrink-0 text-info-500" />
        <span>{{ $mode }}</span>
    </div>
</div>
