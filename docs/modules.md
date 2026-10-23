# Módulos funcionales

## Instituciones y geografía

Países, provincias, ciudades, federaciones y clubes forman la estructura institucional. El club puede actuar como sede, institución de un jugador, responsable de deuda y ámbito de un gestor. La geocodificación se ejecuta con `app:geocode-clubs` cuando hay direcciones pendientes.

## Usuarios, roles y alcance

Los roles globales se complementan con alcance por disciplina y, para gestores, por club. Filament Shield genera permisos de recursos; las acciones especiales usan permisos propios. Una consulta pública hereda únicamente capacidades del rol `Jugador`.

## Jugadores

La ficha conserva identidad, club, foto y datos deportivos. La afiliación a disciplinas y el historial de categoría tienen vigencia temporal. Los cambios futuros se aplican mediante comandos, no reemplazando el pasado.

## Afiliaciones y membresías

`AnnualMembershipService` configura ciclos, renueva obligaciones, crea deuda institucional, procesa renovaciones manuales, envía confirmación y vence deudas atrasadas. La fuente del pago queda registrada.

## Transferencias bancarias

`BankTransferService` crea órdenes para membresías o inscripciones, recibe comprobantes y permite aprobar, rechazar o eliminar según estado. Un vencimiento programado cierra órdenes no completadas. Las acciones deben registrar revisor, fecha y motivo.

## Rankings y categorías

El ranking general y los rankings específicos guardan movimientos e instantáneas. `ThreeCushionRankingService` sincroniza tres bandas. Los procesos de promoción determinan cambios y los aplican cuando llega su fecha efectiva.

Comandos de mantenimiento:

- `ranking:recalculate`;
- `ranking:close-season {season}`;
- `ranking:determine-category-promotions {season}`;
- `ranking:apply-category-promotions`;
- `player-categories:apply-due-changes`;
- importación y siembra histórica mediante los comandos `ranking:*` y `player-categories:*` disponibles.

## Reglamentos

`TournamentRegulationService` evalúa condiciones. `TournamentRegulationWorkflowService` coordina decisiones y auditorías. Los ajustes generales viven en `TournamentRegulationSetting`; una excepción por torneo requiere motivo y registro de auditoría.

## Puntuación de torneos

`TournamentScoringService` administra reglas y asignaciones. La propuesta de puntos puede verse antes de confirmar. La escritura final debe identificar torneo, jugador, instancia y regla para impedir duplicados.

## Notificaciones

`AdminNotifier` selecciona destinatarios administrativos. Los eventos importantes generan notificación persistente y, cuando corresponde, correo. El envío real depende de `MAIL_*` y de que el worker de cola esté activo.

## Publicación

Torneos próximos, rankings y competencias disponen de JSON y PDF. WordPress consume servicios públicos, pero la interfaz para usuarios no debe mostrar referencias técnicas a JSON, WordPress, controladores o desarrollo.
