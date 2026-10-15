<!DOCTYPE html>
<html lang="es">
<body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#172033">
<div style="max-width:680px;margin:24px auto;background:#fff;border:1px solid #dbe2ea;border-radius:10px;overflow:hidden">
    <div style="background:#173f8f;color:#fff;padding:22px 28px">
        <h1 style="margin:0;font-size:22px">Comprobante de membresías</h1>
        <p style="margin:7px 0 0">{{ $membership->discipline?->name }} · Año {{ $membership->year }}</p>
    </div>
    <div style="padding:24px 28px">
        <p>La operación fue registrada correctamente.</p>
        <table style="width:100%;border-collapse:collapse;margin:18px 0">
            <tr><td style="padding:7px;border-bottom:1px solid #e5e7eb"><strong>Usuario</strong></td><td style="padding:7px;border-bottom:1px solid #e5e7eb">{{ $generatedBy->name }}</td></tr>
            <tr><td style="padding:7px;border-bottom:1px solid #e5e7eb"><strong>Fecha</strong></td><td style="padding:7px;border-bottom:1px solid #e5e7eb">{{ $generatedAt->format('d/m/Y H:i') }}</td></tr>
            <tr><td style="padding:7px;border-bottom:1px solid #e5e7eb"><strong>Vencimiento</strong></td><td style="padding:7px;border-bottom:1px solid #e5e7eb">{{ $membership->due_date?->format('d/m/Y') }}</td></tr>
            <tr><td style="padding:7px"><strong>Jugadores procesados</strong></td><td style="padding:7px">{{ $renewals->count() }}</td></tr>
        </table>
        <p>Se adjunta el comprobante PDF con el detalle completo.</p>
    </div>
</div>
</body>
</html>
