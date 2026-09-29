@php
    $record = $getRecord();
    $disk = \Illuminate\Support\Facades\Storage::disk('public_path');
    $logoPath = $record->publicationLogoPath();
    $flyerPath = $record->flyer_path;
@endphp

<div class="flex items-center justify-center gap-1.5">
    @if ($logoPath)
        <a href="{{ $disk->url($logoPath) }}" target="_blank" rel="noopener noreferrer" title="Logo de federación">
            <img
                src="{{ $disk->url($logoPath) }}"
                alt="Logo de federación"
                class="h-9 w-9 rounded-md object-contain"
            >
        </a>
    @endif

    @if ($flyerPath)
        <a href="{{ $disk->url($flyerPath) }}" target="_blank" rel="noopener noreferrer" title="Flyer del torneo">
            <img
                src="{{ $disk->url($flyerPath) }}"
                alt="Flyer del torneo"
                class="h-9 w-12 rounded-md object-cover"
            >
        </a>
    @endif

    @if (! $logoPath && ! $flyerPath)
        <span class="text-xs text-gray-400">—</span>
    @endif
</div>
