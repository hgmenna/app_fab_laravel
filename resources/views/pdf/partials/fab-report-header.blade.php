<header class="fab-report-header">
    <table>
        <tr>
            <td class="fab-report-logo-cell">
                @if (!empty($logo))
                    <img src="{{ $logo }}" alt="FAB" class="fab-report-logo">
                @endif
            </td>
            <td class="fab-report-title-cell">
                <div class="fab-report-title">{{ $reportTitle }}</div>
                <div class="fab-report-subtitle">{{ $reportSubtitle }}</div>
            </td>
            <td class="fab-report-date-cell">Emisión:<br><strong>{{ $generatedAt }}</strong></td>
        </tr>
    </table>
</header>
<footer class="fab-report-footer">
    @if (!empty($footer_image))
        <img src="{{ $footer_image }}" alt="Federación Argentina de Billar">
    @endif
</footer>
