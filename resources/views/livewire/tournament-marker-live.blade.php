<div wire:poll.2s="pollScores" class="fq-card" role="status">
    <strong>Marcadores · {{ $club }}</strong>
    <span class="fq-label">{{ $error ?: 'Los tanteadores se actualizan en cada partido.' }}</span>
    @if($conflicts)
        <p>Hay {{ $conflicts }} resultados que requieren revisión porque cambió la partida o su configuración.</p>
    @endif
</div>
