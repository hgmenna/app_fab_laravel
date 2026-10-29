@php
    $event = $competition->tournament;
    $eventLogo = $event?->publicationLogoPath();
    $eventOrganization = $event?->publicationOrganizationName() ?? 'Organización del torneo';
    $eventInitials = collect(explode(' ', $eventOrganization))->filter()->take(3)->map(fn ($word) => mb_substr($word, 0, 1))->implode('');
    $eventLogoUrl = $eventLogo ? \Illuminate\Support\Facades\Storage::disk('public_path')->url($eventLogo) : null;
    $eventDate = $event?->start_date?->format('d/m/Y') ?? 'Fecha a confirmar';
    if ($event?->end_date && $event?->start_date && ! $event->end_date->isSameDay($event->start_date)) {
        $eventDate .= ' al '.$event->end_date->format('d/m/Y');
    }
    $eventModality = $event?->tournamentModalities?->pluck('modality.name')->filter()->implode(' · ') ?: 'Billar deportivo';
@endphp
<style>
.availability-screen{box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:clamp(24px,4vh,48px);width:100%;min-height:100%;padding:clamp(24px,5vw,90px);background:radial-gradient(ellipse at 15% 10%,#173d59 0,transparent 55%),radial-gradient(ellipse at 90% 90%,#102f34 0,transparent 50%),#071421;color:#f3f7fb;font-family:Inter,"Segoe UI",Arial,sans-serif;text-align:center;line-height:1.4}
.availability-screen *{box-sizing:border-box}.availability-brand{display:flex;align-items:center;gap:16px;color:#b7cbd9;font-size:clamp(12px,1.2vw,18px);font-weight:600;letter-spacing:.14em;text-transform:uppercase}.availability-brand img{width:64px;height:64px;object-fit:contain}.availability-emblem{display:grid;place-items:center;width:54px;height:54px;border:1px solid #50718a;border-radius:50%;color:#c1d9eb;font-size:22px;font-weight:800;letter-spacing:0}.availability-event{max-width:1500px;width:100%}.availability-eyebrow{margin:0 0 12px;color:#a5c1d4;font-size:clamp(12px,1vw,17px);letter-spacing:.2em;text-transform:uppercase}.availability-event h1{margin:0;color:#fff;font-size:clamp(28px,3.5vw,64px);font-weight:750;line-height:1.12;text-wrap:balance}.availability-meta{display:flex;justify-content:center;flex-wrap:wrap;gap:12px 36px;margin-top:24px;color:#b7cbd9;font-size:clamp(14px,1.3vw,22px)}.availability-meta span{max-width:100%;overflow-wrap:anywhere}.availability-meta strong{display:block;margin-bottom:4px;color:#779bb6;font-size:.6em;font-weight:700;letter-spacing:.18em;text-transform:uppercase}.availability-notice{width:min(1150px,100%);padding:clamp(24px,3.5vw,56px);border:1px solid #345066;border-radius:22px;background:linear-gradient(130deg,rgba(33,61,82,.7),rgba(12,30,44,.9));box-shadow:0 24px 70px #0003}.availability-badge{display:inline-flex;align-items:center;gap:9px;margin-bottom:20px;padding:7px 14px;border:1px solid #6b6040;border-radius:99px;color:#e2c783;background:#392f1d66;font-size:clamp(11px,.9vw,15px);font-weight:600;letter-spacing:.1em;text-transform:uppercase}.availability-dot{width:7px;height:7px;border-radius:50%;background:#e2c783}.availability-notice h2{margin:0;font-size:clamp(23px,2.5vw,43px);font-weight:650;line-height:1.2;text-wrap:balance}.availability-notice p{max-width:800px;margin:18px auto 0;color:#b9ccdb;font-size:clamp(16px,1.5vw,25px);line-height:1.6;text-wrap:balance}.availability-footer{color:#7f9aaf;font-size:clamp(11px,.9vw,15px);letter-spacing:.06em}.availability-rule{width:56px;height:3px;margin:0 auto 18px;background:#bfaa70;border-radius:2px}@media(max-width:600px){.availability-brand{letter-spacing:.06em}.availability-brand img{width:44px;height:44px}.availability-meta{gap:16px}.availability-screen{gap:28px}.availability-notice{border-radius:16px}}
</style>
<main class="availability-screen">
    <div class="availability-brand">
        @if($eventLogoUrl)<img src="{{ $eventLogoUrl }}" alt="Logo de {{ $eventOrganization }}">@else<span class="availability-emblem" aria-hidden="true">{{ mb_strtoupper($eventInitials) }}</span>@endif
        <span>{{ $eventOrganization }}</span>
    </div>
    <section class="availability-event" aria-label="Datos del torneo">
        <p class="availability-eyebrow">{{ $event?->type?->name ?? 'Torneo de billar' }} · {{ $eventModality }}</p>
        <h1>{{ $event?->name }}</h1>
        <div class="availability-meta">
            <span><strong>Sede</strong>{{ $event?->venueName() ?? 'Sede a confirmar' }}</span>
            <span><strong>Fecha</strong>{{ $eventDate }}</span>
        </div>
    </section>
    <section class="availability-notice" role="status">
        <div class="availability-badge"><span class="availability-dot" aria-hidden="true"></span>Resultados pendientes de habilitación</div>
        <h2>Información no disponible en este momento</h2>
        <p>Espere a que el director deportivo habilite la visualización de resultados.</p>
    </section>
    <div class="availability-footer"><div class="availability-rule"></div>Información oficial del torneo</div>
</main>
