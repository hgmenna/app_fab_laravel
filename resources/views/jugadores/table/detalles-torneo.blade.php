@php 
    // Lógica extraída de la fuente para filtrar torneos pasados y calcular estadísticas [1]
    $registros = ($record->registrations ?? collect())->filter(function ($registro) { 
        return $registro->tournament?->end_date && $registro->tournament->end_date->isPast(); 
    }); 
    $cantidad = $registros->count(); 
    $promedio = $registros->avg('points'); 
@endphp

<style>
    /* Contenedor principal */
    .torneo-container {
        width: 100%;
        font-family: sans-serif;
        color: #333;
    }

    /* Tarjetas de estadísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-card {
        background-color: #f9fafb;
        padding: 15px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .stat-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 20px;
        font-weight: 800;
        color: #1f2937;
    }

    /* Contenedor de tabla responsive */
    .table-responsive {
        width: 100%;
        overflow-x: auto; /* Permite scroll horizontal en móviles */
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 500px; /* Asegura que la tabla no se amontone */
        background-color: #fff;
    }

    .data-table th {
        background-color: #f3f4f6;
        padding: 12px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #4b5563;
        text-transform: uppercase;
        border-bottom: 2px solid #e5e7eb;
        text-align: center;
    }

    .data-table td {
        padding: 12px 16px;
        font-size: 14px;
        border-bottom: 1px solid #f3f4f6;
        text-align: center;
    }

    .data-table tr:hover {
        background-color: #f9fafb;
    }

    .text-right { text-align: right; }
    .points-highlight { 
        font-weight: 600; 
        color: #4f46e5; 
    }

    /* Media Query para pantallas más grandes */
    @media (min-width: 640px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="torneo-container">
    <!-- Resumen de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Cantidad de Torneos</span>
            <span class="stat-value">{{ $cantidad }}</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Promedio de Puntos</span>
            <span class="stat-value">{{ number_format($promedio, 2) }}</span>
        </div>
    </div>

    <!-- Tabla Adaptable -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Torneo</th>
                    <th>Tipo></th>
                    <th>Fecha</th>
                    <th class="text-right">Puntos</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registros as $registro)
                    <tr>
                        <td>{{ $registro->tournament->name ?? 'N/A' }}</td>
                        <td>{{ $registro->tournament->type->code ?? 'N/A' }}
                        <td>{{ $registro->tournament->end_date->format('d/m/Y') }}</td>
                        <td class="text-right points-highlight">{{ $registro->points }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 30px; color: #9ca3af; font-style: italic;">
                            No se encontraron registros de torneos finalizados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>