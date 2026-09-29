@php
    $record = $getRecord();
    $official = (bool) $record->type?->is_official;
    $handicap = (bool) $record->type?->has_handicap;
@endphp

<div class="flex flex-col items-start gap-1.5 text-xs leading-none">
    <div class="flex items-center gap-1.5" title="{{ $official ? 'Oficial' : 'No oficial' }}">
        <span class="w-7 font-semibold">OF</span>
        <x-filament::icon
            :icon="$official ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'"
            @class([
                'h-5 w-5 shrink-0',
                'text-success-500' => $official,
                'text-danger-500' => ! $official,
            ])
        />
    </div>

    <div class="flex items-center gap-1.5" title="{{ $handicap ? 'Con handicap' : 'Sin handicap' }}">
        <span class="w-7 font-semibold">HAN</span>
        <x-filament::icon
            :icon="$handicap ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'"
            @class([
                'h-5 w-5 shrink-0',
                'text-success-500' => $handicap,
                'text-danger-500' => ! $handicap,
            ])
        />
    </div>
</div>
