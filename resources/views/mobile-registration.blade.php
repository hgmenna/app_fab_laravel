<!doctype html>
<html lang="es">
<head>@include('partials.mobile-pwa-head')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anotarse · FAB</title>
    <link rel="stylesheet" href="{{ asset('fab-mobile/app.css') }}">
    <script type="module" src="{{ asset('fab-mobile/registration.js') }}"></script>
</head>
<body>@include('partials.mobile-header')
<main class="wrap registration-screen">
    <a class="back" href="{{ route('mobile-app') }}">‹ Calendario</a>
    <h1>Anotarse</h1>
    <h2>{{ $tournament->name }}</h2>
    @if ($confirmation && $confirmation['tournament_id'] === $tournament->id)
        <p role="status">Inscripción registrada.</p>
        @if ($confirmation['payment_url'])
            <a class="icon-button button" href="{{ $confirmation['payment_url'] }}" aria-label="Continuar con el pago" title="Continuar con el pago"><x-fab-icon name="payment" /></a>
        @endif
    @elseif (! $tournament->isRegistrationOpen())
        <p role="status">La inscripción está cerrada.</p>
    @elseif ($modalities->isEmpty())
        <p role="status">El torneo todavía no tiene modalidades habilitadas.</p>
    @else
        @if (!empty($localPreview))<p class="muted">Vista previa. El jugador y los horarios son de demostración.</p>@endif
        @if ($errors->any())
            <ul class="registration-errors" role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <form id="registration" action="{{ route('mobile-registration.store', ['tournament' => $tournament->id]) }}" method="post"
            data-preview="{{ !empty($localPreview) ? 'yes' : 'no' }}" data-options="{{ route('mobile-registration.options', ['tournament' => $tournament->id]) }}">
            @csrf
            <label @if($modalities->count() === 1) hidden @endif>Modalidad
                <select name="tournament_modality_id" required>
                    @if ($modalities->count() > 1)<option value="">Elegí la modalidad</option>@endif
                    @foreach ($modalities as $item)
                        <option value="{{ $item->id }}" @selected((string) old('tournament_modality_id') === (string) $item->id)>{{ $item->modality->name }}</option>
                    @endforeach
                </select>
            </label>
            <p id="registration-status" role="status"></p>
            <div id="participants" data-selected="{{ json_encode(old('player_ids', [])) }}"></div>
            <label id="registration-slot" hidden>Horario
                <select name="tournament_slot_id" data-selected="{{ old('tournament_slot_id') }}"></select>
            </label>
            <button type="submit" disabled class="icon-button" aria-label="Confirmar inscripción" title="Confirmar inscripción"><x-fab-icon name="check" /></button>
        </form>
    @endif
</main>
@include('partials.mobile-footer')
</body>
</html>
