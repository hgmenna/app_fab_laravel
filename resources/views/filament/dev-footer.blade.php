<footer style="width:100%; margin-top:6px; padding:7px clamp(12px,3vw,32px); border-top:2px solid #e5e7eb; background:#000; color:#fff; font-family:Arial,sans-serif; box-sizing:border-box;">
    <div class="fab-footer-grid" style="display:grid; grid-template-columns:minmax(0,1.1fr) minmax(0,1.25fr) minmax(0,1fr); align-items:center; gap:12px; line-height:1.2;">
        <div style="min-width:0;">
            <div style="font-size:clamp(.58rem,1vw,.7rem); font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#d1d5db;">Desarrollo y Arquitectura de Software</div>
            <div style="margin-top:2px; font-size:clamp(.78rem,1.2vw,1rem); font-weight:900;">Hernán Gabriel Menna</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:3px 14px; font-size:clamp(.6rem,1vw,.72rem); color:#e5e7eb; text-align:center;">
            <span><strong style="color:#f59e0b;">☎</strong> +54 9 341 598 8191</span>
            <span><strong style="color:#f59e0b;">✉</strong> hgmenna@hotmail.com</span>
            <span><strong style="color:#f59e0b;">●</strong> Rosario - Santa Fe - Argentina</span>
        </div>
        <div style="min-width:0; text-align:right;">
            <span style="display:inline-block; padding:2px 8px; border-radius:9999px; background:#f3f4f6; color:#111827; font:600 clamp(.58rem,1vw,.68rem)/1.2 monospace;">Versión {{ config('app.version') }}</span>
            <div style="margin-top:3px; font-size:clamp(.56rem,.9vw,.67rem); font-weight:600; color:#d1d5db;">Sistema Administrativo Federación Argentina de Billar</div>
        </div>
    </div>
</footer>
<style>
    @media (max-width:700px) {
        .fab-footer-grid { grid-template-columns:1fr !important; gap:4px !important; text-align:center !important; }
        .fab-footer-grid > div { text-align:center !important; }
    }
</style>
