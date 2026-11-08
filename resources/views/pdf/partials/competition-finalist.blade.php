@php
    $photoFile = filled($finalist?->photo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public_path')->path($finalist->photo_path)
        : null;
    $size = $photoFile && is_file($photoFile) ? @getimagesize($photoFile) : false;
    $scale = $size ? 78 / max($size[0], $size[1]) : null;
@endphp
<div class="finalist-print">
    <div class="finalist-print-frame">
        @if($scale)
            <img src="{{ $photoFile }}" width="{{ max(1, round($size[0] * $scale)) }}" height="{{ max(1, round($size[1] * $scale)) }}" alt="">
        @else
            {{ $finalist ? mb_substr($finalist->last_name, 0, 1).mb_substr($finalist->first_name, 0, 1) : '—' }}
        @endif
    </div>
    {{ $finalist?->full_name ?? 'A designar' }}
</div>
