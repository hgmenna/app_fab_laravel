{{-- resources/views/jugadores/table/detalles-torneo.blade.php --}}
@php
        // Obtenemos los registros y calculamos totales al inicio
        $registros = ($record->registrations ?? collect())->filter(function ($registro) {
            return $registro->tournament?->end_date && $registro->tournament->end_date->isPast();
        });
        $cantidad = $registros->count();
        $promedio = $registros->avg('points');
    @endphp

    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: center;">
        <thead style="background-color: green; color: white;">
            <tr style="border-bottom: 2px solid #f3f4f6;">
                <th style="padding: 0.5rem; font-size: 0.75rem; text-transform: uppercase; text-align:center;">Torneo</th>
                <th style="padding: 0.5rem; font-size: 0.75rem; text-transform: uppercase; text-align:center;">Tipo</th>
                <th style="padding: 0.5rem; font-size: 0.75rem; text-transform: uppercase; text-align:center;">Fecha Fin</th>
                <th style="padding: 0.5rem; font-size: 0.75rem; text-transform: uppercase; text-align:center;">Instancia</th>
                <th style="padding: 0.5rem; font-size: 0.75rem; text-transform: uppercase; text-align:center;">Puntos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $registro)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.75rem 0.5rem; font-weight: bold; color: #fff; text-align: center;">
                        {{ $registro->tournament?->name }}
                    </td>
                    <td style="padding: 0.75rem 0.5rem; color: #fff; text-align: center;">
                        {{ $registro->tournament?->type?->name }}
                    </td>
                    <td style="padding: 0.75rem 0.5rem; color: #fff; text-align: center;">
                        {{ $registro->tournament?->end_date?->format('d/m/Y') ?? 'N/A' }}
                    </td>
                    <td style="padding: 0.75rem 0.5rem; color: #fff; font-style: italic; text-align: center;">
                        {{ $registro->tournamentInstance?->description }}
                    </td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right;">
                        <span style="padding: 0.25rem 0.5rem; font-size: 1rem; font-weight: bold; color: #fff;">
                            {{ number_format($registro->points, 0) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding: 1rem; text-align: center; color: #9ca3af; font-style: italic;">
                        No hay torneos registrados para este jugador.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- SECCIÓN DE RESUMEN CON ESTILOS EN LÍNEA --}}
    @if($cantidad > 0)
        <div style="align-items: center; background-color: green; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 2rem; font-size: 0.875rem; font-weight: bold;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: white; font-weight: 500; align-items: center; ">Cantidad de torneos:</span>
                <span style="color: white; font-weight: 900; font-size: 1.25rem; padding: 0.125rem 0.5rem;align-items: center; ">
                    {{ $cantidad }}
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: white; font-weight: 500;align-items: center; ">Promedio de puntos:</span>
                <span style="align-items: center; color: white; font-weight: 900; padding: 0.125rem 0.5rem; font-size: 1.25rem">
                    {{ number_format($promedio, 2) }}
                </span>
            </div>
        </div>
    @endif
</div>