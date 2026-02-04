<style>
    .fab-table {
        border-collapse: separate !important;
        border-spacing: 0 10px !important;
        width: 100%;
        font-size: 0.875rem;
    }

    .fab-table thead th {
        background-color: #064E3B;
        color: #FFF;
        font-weight: 600;
        text-transform: uppercase;
        padding: 10px 16px;
        font-size: 0.75rem;
        border-radius: 6px;
    }

    .fab-table tbody td {
        background-color: #ffffff;
        color: #111827;
        padding: 10px 16px;
        border-radius: 6px;
        white-space: nowrap;
    }

    .fab-row {
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .fab-empty {
        padding: 16px;
        text-align: center;
        color: #6b7280;
        font-size: 0.875rem;
    }
</style>


<table class="fab-table text-sm">
    <thead>
        <tr>
            <th>Jugador</th>
            <th>Club</th>
            <th>Categoría</th>
            <th>Horario</th>
        </tr>
    </thead>

    <tbody>
        @forelse ($registrations as $reg)
            <tr class="fab-row">
                <td>{{ $reg->player->full_name }}</td>
                <td>{{ $reg->player->club->name }}</td>
                <td>{{ $reg->player->category->name }}</td>
                <td>{{ $reg->slot->name }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="py-4 text-center text-gray-500">
                    No hay inscriptos en este horario.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>