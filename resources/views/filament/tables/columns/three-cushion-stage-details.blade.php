@php($stages = $getRecord()->stageDetails())

<details class="min-w-80">
    <summary class="cursor-pointer font-semibold text-primary-600 dark:text-primary-400">
        Ver {{ $stages->count() }} etapa{{ $stages->count() === 1 ? '' : 's' }}
    </summary>

    <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
        <table class="w-full min-w-[760px] text-xs">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="p-2 text-left">Etapa</th>
                    <th class="p-2 text-center">Posición</th>
                    <th class="p-2 text-right">Car.</th>
                    <th class="p-2 text-right">Entr.</th>
                    <th class="p-2 text-right">Prom. Gral.</th>
                    <th class="p-2 text-right">Mejor Prom.</th>
                    <th class="p-2 text-right">Serie</th>
                    <th class="p-2 text-right">Puntos</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stages as $stage)
                    <tr class="border-t border-gray-200 dark:border-white/10">
                        <td class="p-2">
                            <strong>{{ $stage->registration?->tournament?->name }}</strong><br>
                            <span class="text-gray-500">{{ $stage->registration?->tournament?->end_date?->format('d/m/Y') }}</span>
                        </td>
                        <td class="p-2 text-center">{{ $stage->registration?->result_description ?? $stage->registration?->tournamentInstance?->description }}</td>
                        <td class="p-2 text-right">{{ $stage->caroms }}</td>
                        <td class="p-2 text-right">{{ $stage->innings }}</td>
                        <td class="p-2 text-right">{{ number_format((float) $stage->general_average, 3, ',', '.') }}</td>
                        <td class="p-2 text-right">{{ number_format((float) $stage->best_match_average, 3, ',', '.') }}</td>
                        <td class="p-2 text-right">{{ $stage->high_run }}</td>
                        <td class="p-2 text-right font-semibold">{{ number_format((float) $stage->registration?->points, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-3 text-center text-gray-500">Sin etapas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</details>
