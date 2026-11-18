# Organización de torneos en FAB Movil

La ruta `GET /app/torneos/{tournament}/organizacion` mantiene al usuario en la aplicación móvil. Requiere correo verificado y el permiso `ManageTournamentCompetition` para el torneo. Actualmente esta organización corresponde a cinco quillas.

## Archivos y responsabilidades

- `app/Http/Controllers/MobileTournamentOrganizationController.php`: autoriza la entrada y prepara los recursos de formularios.
- `resources/views/mobile-tournament-organization.blade.php`: documento móvil, encabezado, pie, recursos y componente operativo. No redirige al panel administrativo.
- `app/Livewire/MobileTournamentOrganization.php`: componente interactivo. Reutiliza las acciones de `ManageTournamentCompetition`, revalida permisos al recibir cada petición y conserva fase, zona y sección seleccionadas. Adapta la tabla de configuración de zonas a campos verticales sin duplicar la validación ni la escritura.
- `resources/views/livewire/mobile-tournament-organization.blade.php`: menú de configuración/publicación, fases, zonas, sorteos, emparejamientos, grilla, posiciones y jugadores pendientes.
- `resources/views/mobile/organization-match.blade.php`: puntaje por set y acciones de un partido de zona, incluyendo llamado, envío a marcador, cancelación y resultados.
- `resources/views/mobile/organization-style.blade.php`: presentación y áreas táctiles de esta pantalla; no modifica la interfaz del panel administrativo.
- `resources/views/filament/resources/tournaments/pages/partials/competition-bracket.blade.php`: representación compartida de los cruces con acciones; su disposición móvil se controla desde el estilo móvil.

## Reglas compartidas con FAB

Las operaciones siguen definidas en `app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php` y `app/Services/FiveQuillasCompetitionService.php`. Modificar allí una validación se aplica a ambos accesos. La integración con tablets continúa usando `TournamentMarkerService` y `TournamentMarkerLive`.

El componente de la app hereda acciones de configuración de formato y publicación, inicialización, preparación de fases, asignación de horarios, zonas, sorteos, marcadores, resultados y puntuación final. Los identificadores de partidos se resuelven dentro de la competencia abierta. La revisión del partido evita sobrescribir cambios de otro operador.

Este cambio habilita las operaciones de competencia dentro de un torneo existente; no incorpora una nueva pantalla de alta de torneos ni sustituye las demás secciones administrativas de FAB.

## Verificación

`tests/Feature/MobileTournamentOrganizationTest.php` comprueba los controles operativos, la pantalla móvil completa, el formulario vertical de zonas, la ejecución de inicialización y los rechazos por falta de verificación, cierre de sesión y permisos revocados.

El caso «guarda desde la acción móvil» de `tests/Feature/TournamentMarkerTest.php` ejecuta la acción heredada, verifica ganador y sets persistidos y rechaza partidos de otra competencia.

## Actualización

No cambia el paquete Android: las pantallas se cargan desde el servidor. En la carpeta `app-production` ejecutar:

```bash
git pull --ff-only origin main && php artisan optimize:clear
```

Cerrar y volver a abrir la pantalla de organización. La versión de interfaz indicada en el pie es 1.0.4.
