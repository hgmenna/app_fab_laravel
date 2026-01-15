<div class="filament-tables-component overflow-x-auto">
    <div class="filament-tables-container w-full overflow-hidden rounded-xl border border-gray-300 dark:border-gray-700">
        <table class="min-w-full table-auto divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Jugador
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Categoría
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                        Activo
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($players as $player)
                    <tr class="divide-x divide-gray-200 dark:divide-gray-700">
                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $player->full_name }}
                        </td>

                        <td class="px-4 py-2 whitespace-nowrap">
                            {{ $player->category->name ?? '-' }}
                        </td>

                        <td class="px-4 py-2 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $player->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $player->is_active ? 'Sí' : 'No' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

