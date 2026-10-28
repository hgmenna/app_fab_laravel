@php
    $sectionPdfUrl = route('five-quillas-competition.section-pdf', ['slug' => $competition->public_slug, 'section' => $sectionKey]);
@endphp
<div class="fq-section-tools" aria-label="Acciones de {{ $sectionTitle }}">
    <button type="button" class="fq-icon-button" title="Compartir {{ $sectionTitle }}" aria-label="Compartir {{ $sectionTitle }}" @click="window.fqSharePdf($el.parentElement.querySelector('a').href, @js($tournamentInfo['name'].' · '.$sectionTitle))">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4.2M8.6 13.4l6.8 4.2"/></svg>
    </button>
    <a class="fq-icon-button" href="{{ $sectionPdfUrl }}" @if($filterZoneSlot ?? false) :href="@js($sectionPdfUrl) + '?slot=' + encodeURIComponent(activeSlot)" @endif target="_blank" title="Exportar {{ $sectionTitle }} a PDF" aria-label="Exportar {{ $sectionTitle }} a PDF">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg>
    </a>
</div>

@once
    @script
    <script>
        @include('partials.competition-pdf-script')
    </script>
    @endscript
@endonce
