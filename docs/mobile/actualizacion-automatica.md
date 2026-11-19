# Actualización automática de FAB Movil — 1.0.7

La actualización es general; cada pantalla consulta al servidor sin reemplazar lo que el usuario está escribiendo. No depende de cerrar y volver a instalar la PWA.

| Sección | Actualización |
| --- | --- |
| Calendario, torneos nuevos y cambios de inscripción | Cada 30 segundos; también al recuperar foco, visibilidad o conexión y al volver desde rankings. |
| Rankings | Cada 30 segundos mientras la sección está visible; conserva búsqueda, categoría y cantidad desplegada. |
| Padrón y desempeño | Cada 30 segundos y al volver a la app. Conserva filtros aplicados, fichas desplegadas y posición del registro visible. |
| Cuenta y navegación | Reconsulta sesión y permisos visibles. No reemplaza un formulario de acceso que se está completando. |
| Opciones de filtros | Se reconsultan disciplinas, categorías, tipos y federaciones. |
| Inscripción | Modalidades, jugadores elegibles y cupos cada 15 segundos; comprobación adicional inmediatamente antes del envío. |
| Organización | Conserva la escucha de cambios de competencia y marcadores; agrega relectura general cada 30 segundos cuando no hay una acción abierta. |
| Resultados públicos | Cada 10 segundos, y también al recuperar foco, visibilidad o conexión. Si el acceso se retira, deja de mostrar el resultado anterior. |

Las consultas usan `cache: no-store`. Las tareas no consultan una sección inactiva ni generan solicitudes superpuestas. Después de un fallo de red se conservan los datos disponibles, se avisa cuando corresponde y se reintenta.

## Cupos

`TournamentSlotAvailability` publica solamente turnos activos que todavía tienen cupo. Si una modalidad tiene horarios configurados, la inscripción móvil exige elegir uno: no permite eludir un horario completo enviando una selección vacía. Si se completa el horario seleccionado, desaparece de las opciones y la confirmación queda deshabilitada hasta elegir otro.

La comprobación del navegador no reserva un lugar. El servidor vuelve a validar dentro de una transacción. `TournamentRegistration::save()` toma un bloqueo sobre el torneo antes de contar cupos y persistir: las altas de la web y la app comparten la misma exclusión. También se revalidan cambios de horario y estado de una inscripción existente. La cantidad de cupos cuenta inscripciones, no integrantes de un equipo.

## Archivos

- `public/fab-mobile/live-refresh.js`: programación compartida por todas las secciones y eventos de retorno/conexión.
- `public/fab-mobile/live-updates.js`: actualización de regiones de lectura, opciones de filtros y navegación; preserva formularios.
- `app/Http/Controllers/MobileAppStateController.php`: `/app/estado.json`, encabezado autorizado y opciones vigentes.
- `public/fab-mobile/app.js`: conciliación de torneos por identificador, sin reconstruir tarjetas sin cambios.
- `public/fab-mobile/ranking.js`: ranking activo y conservación de los filtros.
- `public/fab-mobile/results.js`: resultados, recuperación y retiro de información que ya no está publicada.
- `public/fab-mobile/registration.js`: actualización incremental de integrantes, turnos y comprobación previa al envío.
- `app/Services/TournamentSlotAvailability.php`: disponibilidad y validación final de horarios.
- `app/Models/TournamentRegistration.php`: transacción y validación compartidas por web y móvil.
- Vistas `mobile-players`, `mobile-player-performance`, `mobile-account` y `mobile-registration`: delimitan las regiones que pueden refrescarse sin perder entradas del usuario.

## Pruebas

Las pruebas de JavaScript verifican programación, pausas, reconexión y retorno. Las de inscripción cubren horarios completos, selección faltante, cupo liberado, horarios deshabilitados, clientes sucesivos y edición de horario hacia un turno completo. Las pruebas SQLite validan las reglas; no simulan dos procesos MySQL ejecutándose simultáneamente.

En Chrome con respuestas controladas se comprobó: pasar de uno a tres torneos sin cerrar detalles; conservar información ante error 503; y ocupar el último cupo entre selección y confirmación, sin emitir una solicitud de alta.
