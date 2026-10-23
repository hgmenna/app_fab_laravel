# Referencia técnica

Esta guía enumera los puntos de entrada. Consulte firmas y tipos en el código antes de invocarlos.

## Servicios centrales

### `FiveQuillasCompetitionService`

| Grupo | Operaciones principales |
|---|---|
| Inicio | `initialize`, `syncApprovedEntries`, `refreshEntryPhaseAssignments` |
| Formato | `configureFormat`, `applyCompetitionTemplate` |
| Zonas/cruces | `createManualZones`, `createManualSlotElimination` |
| Preparación | `preparePhase`, `scheduleConfiguredMatches` |
| Participantes | `drawCandidates`, `bracketParticipantRows`, `bracketParticipantDisplayRows`, `qualifiedParticipantRows`, `bracketMatchupRows` |
| Edición | `saveBracketParticipantOrder`, `saveDrawNumbers`, `correctMatchParticipants` |
| Resultados | `recordMatchResult`, `declareMatchWinner`, `resetMatchResult` |
| Progresión | `previousPhaseFor`, `slotEliminationCandidates`, `reopenIncompleteConcurrentPhases` |
| Clasificación | `recalculateFinalStandings`, `ensureFinalStandingsOrder`, `finalStandingPerformance` |
| Puntos | `assignSelectedScoringRule`, `proposedPointsForPlacement`, `proposedPointsForEntry`, `commitFinalScoring` |

Estas operaciones deben ser la vía preferida. No replique su lógica en páginas o controladores.

### Otros servicios

- `AnnualMembershipService`: ciclo anual, deuda, renovación, confirmación y vencimiento.
- `BankTransferService`: órdenes, presentación, aprobación, rechazo y eliminación.
- `PlayerCategoryHistoryService`: categoría por afiliación, torneo, ranking, cambio manual/temporal y promociones.
- `TournamentScoringService`: reglas de puntaje y asignaciones.
- `TournamentRegulationService`: evaluación reglamentaria.
- `TournamentRegulationWorkflowService`: aplicación y auditoría del flujo reglamentario.
- `ThreeCushionRankingService`: sincronización del ranking de tres bandas.

## Controladores públicos

Los controladores de `app/Http/Controllers` atienden:

- torneos próximos y publicados;
- rankings de cinco quillas y tres bandas;
- datos públicos de competencia;
- página pública de resultados;
- PDF de rankings, inscripciones, membresías, transferencias y secciones de competencia.

Un controlador público transforma modelos o llama servicios; no debe ejecutar reglas de clasificación distintas de administración.

## Página de organización

`ManageTournamentCompetition` coordina acciones Filament para formato, zonas, llaves, horarios, resultados, clasificados y puntos. Su Blade asociado representa fases y grillas. Mantenga la lógica de negocio en el servicio y la lógica de visualización en la vista.

## Comandos

Use `php artisan list` para argumentos actuales.

| Comando | Finalidad |
|---|---|
| `ranking:apply-category-promotions` | aplica promociones pendientes |
| `player-categories:apply-due-changes` | aplica cambios con vigencia cumplida |
| `ranking:close-season` | cierra temporada |
| `ranking:determine-category-promotions` | calcula promociones |
| `memberships:expire-overdue` | vence membresías atrasadas |
| `bank-transfers:expire` | vence órdenes pendientes |
| `app:geocode-clubs` | completa coordenadas |
| `ranking:recalculate` | recalcula ranking |
| `tournaments:sync-scoring` | sincroniza puntuación de torneos |

## Scheduler

`routes/console.php` ejecuta en `America/Argentina/Buenos_Aires`:

| Hora | Proceso |
|---|---|
| 00:05 | promociones de ranking |
| 00:10 | cambios de categoría |
| 00:15 | membresías vencidas |
| 00:20 | transferencias vencidas |

Todos usan `withoutOverlapping`.

## Puntos de extensión

- Nuevo CRUD: Resource, páginas, Policy, permisos y pruebas de acceso.
- Nueva regla deportiva: servicio, persistencia histórica, administración, público, PDF y prueba.
- Nuevo método de clasificación: configuración, candidatos, orden, columnas de la lista, snapshot y propagación.
- Nuevo formato: plantilla, validación, preparación de fases y prueba integral.
- Nuevo documento: vista exclusiva de impresión con encabezado/pie repetibles.
