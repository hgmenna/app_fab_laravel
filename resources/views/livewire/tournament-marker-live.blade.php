<div wire:poll.2s.keep-alive="pollScores" @if(! $error && ! $conflicts) hidden @endif>
    @if($error || $conflicts)
        <div class="fq-card" role="status">
            @if($error)
                <p>Marcadores · {{ $error }}</p>
            @endif
            @if($conflicts)
                <p>Hay {{ $conflicts }} resultados de marcadores que requieren revisión porque cambió la partida o su configuración.</p>
            @endif
        </div>
    @endif
</div>
