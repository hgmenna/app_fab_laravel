<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.6; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .header { background-color: #1e293b; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .field-group { margin-bottom: 15px; border-bottom: 1px dotted #cbd5e1; padding-bottom: 5px; }
        .label { font-weight: bold; color: #64748b; font-size: 12px; text-transform: uppercase; }
        .value { font-size: 16px; color: #1e293b; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .pendiente { background-color: #fef3c7; color: #92400e; }
        .receipt-container { margin-top: 20px; text-align: center; border: 1px solid #e2e8f0; padding: 10px; border-radius: 5px; }
        .receipt-img { max-width: 100%; height: auto; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Comprobante de Inscripción</h1>
            <p>{{ $record->tournament->name }}</p>
        </div>
        <div class="content">
            <div class="field-group">
                <div class="label">Estado de Inscripción</div>
                <div class="value"><span class="status-badge {{ $record->status }}">{{ strtoupper($record->status) }}</span></div>
            </div>
            
            <div class="field-group">
                <div class="label">Jugador</div>
                <div class="value">{{ $record->player->last_name }}, {{ $record->player->first_name }}</div>
            </div>

            <div class="field-group">
                <div class="label">Club / Categoría</div>
                <div class="value">{{ $record->player->club->name }} - {{ $record->player->category->name }}</div>
            </div>

            <div class="field-group">
                <div class="label">Horario Seleccionado</div>
                <div class="value">{{ $record->slot->name }}</div>
            </div>

            @if($record->payment_file)
                <div style="margin-top: 20px; text-align: center; border: 1px solid #e2e8f0; padding: 10px; border-radius: 5px;">
                    @if($record->payment_file && !str_ends_with($record->payment_file, '.pdf'))
                        <p style="margin-bottom: 10px;"><strong>Comprobante de Pago:</strong></p>
                        
                        {{-- Imagen con los estilos de tu clase .receipt-img (max-width 150px para achicarla) --}}
                        <img src="data:image/png;base64,{{ base64_encode(Storage::disk('public')->get($record->payment_file)) }}" 
                            style="max-width: 200px; height: auto; border-radius: 4px; display: inline-block;">

                    @elseif($record->payment_file && str_ends_with($record->payment_file, '.pdf'))
                        {{-- Estructura para centrar el aviso de PDF --}}
                        <div style="display: inline-block; text-align: left;">
                            <p style="margin: 0; font-size: 14px;">📄 <strong>Comprobante en PDF</strong></p>
                            <a href="{{ Storage::disk('public')->url($record->payment_file) }}" 
                            style="color: #2563eb; text-decoration: underline; font-weight: bold; font-size: 13px;">
                            Haga clic aquí para ver el documento
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</body>
</html>