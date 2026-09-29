@php($record = $getRecord())

<div class="flex flex-col items-start gap-1 text-xs leading-tight">
    <div class="flex items-center gap-1.5" title="Inicio">
        <x-filament::icon icon="heroicon-m-play" class="h-3.5 w-3.5 shrink-0 text-success-500" />
        <span>{{ $record->start_date?->format('d/m/y') ?? '—' }}</span>
    </div>

    <div class="flex items-center gap-1.5" title="Fin">
        <x-filament::icon icon="heroicon-m-flag" class="h-3.5 w-3.5 shrink-0 text-danger-500" />
        <span>{{ $record->end_date?->format('d/m/y') ?? '—' }}</span>
    </div>
</div>
