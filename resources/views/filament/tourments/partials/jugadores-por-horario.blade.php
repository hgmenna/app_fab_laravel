<table class="min-w-full text-sm">
    <thead>
        <tr styie='bg-green-500 text-white !important;'>
            @foreach ($columnas as $col)
                <th class="px-4 py-2 font-semibold text-center border-b">
                    {{ $col['header'] }}
                </th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @for ($i = 0; $i < $maxFilas; $i++)
            <tr class = 'border-b border-white'>
                @foreach ($columnas as $col)
                    <td class="px-4 py-2 text-center border-b">
                        {{ $col['jugadores'][$i] ?? '' }}
                    </td>
                @endforeach
            </tr>
        @endfor
    </tbody>
</table>


