<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría reglamentaria {{ $audit->id }}</title>
    <style>
        @page { margin: 30px; }
        body { color: #172554; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #1d4ed8; margin-bottom: 18px; padding-bottom: 10px; }
        .logo { float: left; max-height: 55px; max-width: 70px; }
        h1 { color: #1e3a8a; font-size: 18px; margin: 0; text-align: center; }
        .subtitle { color: #475569; margin-top: 5px; text-align: center; }
        h2 { background: #1e40af; color: #fff; font-size: 12px; margin: 18px 0 0; padding: 7px; }
        h3 { color: #1e3a8a; font-size: 11px; margin: 14px 0 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #94a3b8; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; font-weight: bold; width: 34%; }
        .status { border: 1px solid #dc2626; background: #fee2e2; color: #991b1b; font-weight: bold; padding: 6px; text-align: center; }
        .status.approved { border-color: #16a34a; background: #dcfce7; color: #166534; }
        .status.overridden { border-color: #d97706; background: #fef3c7; color: #92400e; }
        .conflict { margin-bottom: 12px; page-break-inside: avoid; }
        .evidence { margin-top: 10px; page-break-inside: avoid; }
        .evidence img { border: 1px solid #94a3b8; display: block; margin-top: 6px; max-height: 430px; max-width: 100%; }
        .footer { color: #64748b; font-size: 8px; margin-top: 18px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logo)
            <img src="{{ $logo }}" class="logo" alt="FAB">
        @endif
        <h1>INFORME DE AUDITORÍA REGLAMENTARIA</h1>
        <div class="subtitle">Federación Argentina de Billar · Auditoría N.º {{ $audit->id }}</div>
        <div style="clear: both;"></div>
    </div>

    <div class="status {{ $audit->result }}">
        Resultado:
        {{ match ($audit->result) {
            'approved' => 'APROBADO',
            'overridden' => 'EXCEPCIÓN AUTORIZADA',
            default => 'BLOQUEADO',
        } }}
    </div>

    <h2>Datos de la auditoría</h2>
    <table>
        <tr><th>Fecha y hora</th><td>{{ $audit->created_at?->format('d/m/Y H:i:s') }}</td></tr>
        <tr><th>Usuario responsable</th><td>{{ $audit->user?->name ?? 'Usuario no disponible' }}</td></tr>
        <tr><th>Operación</th><td>{{ $audit->operation === 'create' ? 'Creación' : 'Modificación' }}</td></tr>
        <tr><th>Excepción reglamentaria</th><td>{{ $audit->overridden ? 'Sí' : 'No' }}</td></tr>
        <tr><th>Motivo de la excepción</th><td>{{ $audit->override_reason ?: 'No corresponde' }}</td></tr>
    </table>

    <h2>Torneo evaluado</h2>
    <table>
        <tr><th>Nombre</th><td>{{ $audit->tournament?->name ?? data_get($snapshot, 'name', 'Sin informar') }}</td></tr>
        <tr><th>Disciplina</th><td>{{ $discipline?->name ?? 'Sin informar' }}</td></tr>
        <tr><th>Tipo de torneo</th><td>{{ $type?->name ?? 'Sin informar' }}</td></tr>
        <tr><th>Club organizador</th><td>{{ $club?->name ?? 'Sin informar' }}</td></tr>
        <tr><th>Fecha de inicio</th><td>{{ data_get($snapshot, 'start_date', $audit->tournament?->start_date?->format('d/m/Y')) }}</td></tr>
        <tr><th>Fecha de finalización</th><td>{{ data_get($snapshot, 'end_date', $audit->tournament?->end_date?->format('d/m/Y')) }}</td></tr>
        <tr><th>Categorías</th><td>{{ implode(', ', $categoryNames) ?: 'Sin informar' }}</td></tr>
    </table>

    <h2>Incumplimientos detectados</h2>
    @forelse ($audit->conflicts ?? [] as $index => $conflict)
        @php
            $rule = data_get($conflict, 'rule');
            $route = data_get($conflict, 'route', []);
            $ruleLabel = match ($rule) {
                'exclusive_dates' => 'Exclusividad durante las fechas',
                'official_same_state' => 'Torneo oficial en la misma provincia',
                'manual_distance_required' => 'Verificación de distancia pendiente',
                'minimum_distance' => 'Distancia mínima reglamentaria',
                default => 'Validación reglamentaria',
            };
            $distance = is_numeric(data_get($route, 'distance_km'))
                ? number_format((float) data_get($route, 'distance_km'), 1, ',', '.').' km'
                : 'Pendiente';
            $minimum = is_numeric(data_get($route, 'minimum_distance_meters'))
                ? number_format(((float) data_get($route, 'minimum_distance_meters')) / 1000, 1, ',', '.').' km'
                : 'No corresponde';
        @endphp
        <div class="conflict">
            <h3>Conflicto {{ $index + 1 }}</h3>
            <table>
                <tr><th>Validación incumplida</th><td>{{ $ruleLabel }}</td></tr>
                <tr><th>Torneo existente</th><td>{{ data_get($conflict, 'conflicting_tournament', 'Sin informar') }}</td></tr>
                <tr><th>Fechas</th><td>{{ data_get($conflict, 'start_date') }} — {{ data_get($conflict, 'end_date') }}</td></tr>
                <tr><th>Club</th><td>{{ data_get($conflict, 'club', 'Sin informar') }}</td></tr>
                <tr><th>Provincia</th><td>{{ data_get($conflict, 'province', 'Sin informar') }}</td></tr>
                <tr><th>Categorías superpuestas</th><td>{{ implode(', ', data_get($conflict, 'shared_categories', [])) ?: 'Ninguna' }}</td></tr>
                @if (in_array($rule, ['manual_distance_required', 'minimum_distance'], true))
                    <tr><th>Distancia real</th><td>{{ $distance }}</td></tr>
                    <tr><th>Distancia mínima permitida</th><td>{{ $minimum }}</td></tr>
                    <tr><th>Dirección de origen</th><td>{{ data_get($route, 'origin_address', 'Sin informar') }}</td></tr>
                    <tr><th>Dirección de destino</th><td>{{ data_get($route, 'destination_address', 'Sin informar') }}</td></tr>
                    <tr><th>Comprobante</th><td>{{ data_get($route, 'evidence_path') ? 'Adjuntado' : 'Pendiente' }}</td></tr>
                @endif
                <tr><th>Explicación</th><td>{{ data_get($conflict, 'message', 'Incumplimiento reglamentario') }}</td></tr>
            </table>
        </div>
    @empty
        <p>No se registraron incumplimientos.</p>
    @endforelse

    <h2>Comprobantes</h2>
    @forelse ($evidence as $item)
        <div class="evidence">
            <strong>Archivo:</strong> {{ basename($item['path']) }}
            @if (! $item['exists'])
                <div>El archivo ya no se encuentra disponible.</div>
            @elseif ($item['is_image'])
                <img src="{{ $item['absolute_path'] }}" alt="Comprobante de Google Maps">
            @else
                <div>Comprobante PDF archivado: {{ $item['path'] }}</div>
            @endif
        </div>
    @empty
        <p>No se adjuntó un comprobante.</p>
    @endforelse

    <div class="footer">
        Documento generado el {{ $generatedAt }}. Los registros de auditoría son inmutables.
    </div>
</body>
</html>
