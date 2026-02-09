<div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; background-color: black;">
    <table style="width: 100%; border-collapse: collapse; text-align: center; font-family: ui-sans-serif, system-ui, sans-serif;">
        <thead style="background-color: green; border-bottom: 1px solid #e5e7eb; color: white">
            <tr>
                <th style="padding: 12px 16px; font-weight: 600; color: white; background-color: green;">Jugador</th>
                <th style="padding: 12px 16px; font-weight: 600; color: white; background-color: green;">Categoría</th>
                <th style="padding: 12px 16px; font-weight: 600; color: white; background-color: green;">Activo</th>
            </tr>
        </thead>
        <tbody style="color: white;">
            @foreach ($players as $player)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 12px 16px;">{{ $player->full_name }}</td>
                    <td style="padding: 12px 16px;">{{ $player->category->name ?? '-' }}</td>
                    <td style="padding: 12px 16px;">
                        <span style="padding: 4px 8px; font-size: 12px; font-weight: 500; {{ $player->is_active ? 'background-color: #dcfce7; color: #166534;' : 'background-color: #fee2e2; color: #991b1b;' }}">
                            {{ $player->is_active ? 'Sí' : 'No' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>