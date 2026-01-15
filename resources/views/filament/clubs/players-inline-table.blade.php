<table class="filament-tables-table w-full text-sm">
    <thead>
        <tr class="bg-gray-50 dark:bg-gray-800">
            <th class="px-4 py-2 text-left font-medium">Jugador</th>
            <th class="px-4 py-2 text-left font-medium">Categoría</th>
            <th class="px-4 py-2 text-left font-medium">Activo</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($players as $player)
            <tr class="border-t border-gray-200 dark:border-gray-700">
                <td class="px-4 py-2">{{ $player->full_name }}</td>
                <td class="px-4 py-2">{{ $player->category->name ?? '-' }}</td>
                <td class="px-4 py-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $player->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $player->is_active ? 'Sí' : 'No' }}
                    </span>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>