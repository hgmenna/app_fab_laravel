<div style="font-family: ui-sans-serif, system-ui, sans-serif; color: #111827; padding: 10px;">
    @php 
        $totalAfiliadosFederacion = 0;
        $totalClubesFederacion = 0; 
    @endphp

    @foreach ($record->states as $state)
        <div style="margin-bottom: 25px; border: 1px solid #e5e7eb; border-radius: 8px; background-color: #f9fafb; overflow: hidden;">
            <!-- Cabecera de Provincia -->
            <div style="padding: 12px 16px; background-color: #374151; color: white; font-weight: 700;">
                Provincia: {{ $state->name }}
            </div>

            <div style="padding: 16px;">
                <!-- Tabla única de Clubes por Provincia -->
                <table style="width: 100%; border-collapse: collapse; background-color: black; border: 1px solid #e5e7eb; font-size: 14px;">
                    <thead>
                        <tr style="background-color: green; text-align: left; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 10px; color: white; font-weight: 600;">Nombre del Club</th>
                            <th style="padding: 10px; color: white; font-weight: 600;">Ciudad</th>
                            <th style="padding: 10px; color: white; font-weight: 600; text-align: center;">Cantidad de Jugadores</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($state->cities as $city)
                            @foreach ($city->clubs as $club)
                                @php 
                                    $cantidad = $club->players_count ?? $club->players->count();
                                    $totalAfiliadosFederacion += $cantidad;
                                    $totalClubesFederacion++; 
                                @endphp
                                <tr style="border-bottom: 1px solid #f3f4f6; color: white;">
                                    <!-- Datos del Club y su Ciudad -->
                                    <td style="padding: 10px;">{{ $club->name }}</td>
                                    <td style="padding: 10px;">{{ $city->name }}</td>
                                    <td style="padding: 10px; text-align: center; font-weight: 500; color: white;">
                                        <!-- Uso de conteo de relación similar a las fuentes -->
                                        {{ $club->players_count ?? $club->players->count() }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

     <!-- Resumen Final Actualizado -->
    <div style="margin-top: 20px; padding: 20px; border: 2px solid #2563eb; border-radius: 8px; background-color: green;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <span style="font-size: 1.1rem; font-weight: 700; color: white;">Total de Clubes en la Federación:</span>
            <span style="font-size: 1.4rem; font-weight: 800; color: white;">
                {{ $totalClubesFederacion }}
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #bfdbfe; pt: 10px;">
            <span style="font-size: 1.1rem; font-weight: 700; color: white;">Total General de Afiliados:</span>
            <span style="font-size: 1.4rem; font-weight: 800; color: white;">
                {{ number_format($totalAfiliadosFederacion, 0, ',', '.') }}
            </span>
        </div>
    </div>
</div>