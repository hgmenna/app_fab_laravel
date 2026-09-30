@php($record = $getRecord())

<div class="flex flex-col items-start gap-1 text-xs leading-tight">
    <div class="flex items-center gap-1.5" title="Apertura de inscripción">
        <x-filament::icon icon="heroicon-m-lock-open" class="h-3.5 w-3.5 shrink-0 text-success-500" />
        <span>{{ $record->registration_open_at?->format('d/m/y') ?? '—' }}</span>
    </div>

    <div class="flex items-center gap-1.5" title="Cierre de inscripción">
        <x-filament::icon icon="heroicon-m-lock-closed" class="h-3.5 w-3.5 shrink-0 text-danger-500" />
        <span>{{ $record->registration_close_at?->format('d/m/y') ?? '—' }}</span>
    </div>
</div>
