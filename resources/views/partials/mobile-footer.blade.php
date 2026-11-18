<footer class="mobile-developer">
    <strong>© {{ date('Y') }} {{ config('cliente.proveedor.nombre') ?: 'Hernán Gabriel Menna' }}</strong>
    <span>{{ config('cliente.proveedor.actividad') ?: 'Desarrollo y Arquitectura de Software' }}</span>
    <div><a href="mailto:{{ config('cliente.proveedor.email') ?: 'hgmenna@hotmail.com' }}">{{ config('cliente.proveedor.email') ?: 'hgmenna@hotmail.com' }}</a>
        <a href="tel:+5493415988191">{{ config('cliente.proveedor.telefono') ?: '+54 9 341 598 8191' }}</a></div>
    <small>FAB Deportes · Versión 1.0.4</small>
</footer>
