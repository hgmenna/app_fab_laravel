@php($categories = $getState() ?? [])

@if (count($categories))
    <div class="grid grid-cols-2 gap-x-1 gap-y-1 text-[0.7rem] leading-tight">
        @foreach ($categories as $category)
            <span class="whitespace-nowrap">{{ $category }}</span>
        @endforeach
    </div>
@else
    <span class="text-xs text-gray-400">—</span>
@endif
