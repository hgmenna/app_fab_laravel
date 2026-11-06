# Guía para encontrar y modificar el sistema

Actualizada el 6 de noviembre de 2026. Esta guía corresponde al código del repositorio, no a una instalación que pudiera tener una versión anterior.

## Encontrar rápidamente las zonas administrativas

La pantalla **Torneos → Organización → Zonas** se dibuja en:

**[manage-tournament-competition.blade.php](../resources/views/filament/resources/tournaments/pages/manage-tournament-competition.blade.php)**

No existe una vista independiente llamada `zonas.blade.php`. El mismo archivo contiene resumen, navegación de fases, zonas, cruces, grillas y posiciones finales. Por eso buscar solamente un archivo con la palabra «zona» no permite localizarlo.

En ese archivo, buscar estos textos con Ctrl+F:

| Qué modificar | Texto para buscar |
|---|---|
| Contenedor y selección de fase de zonas | `$zonePhase` |
| Pestañas de horarios de zonas | `fq-slot-tabs` |
| Nombre y encabezado de la zona | `fq-zone-head` |
| Distribución de los partidos dentro de la zona | `fq-zone-bracket` |
| Nombres y puntos de cada jugador | `fq-match-player` |
| Tabla de posiciones de la zona | `fq-standings` |
| Presentación en móvil | `@media` |
| Listado de posiciones del torneo | `Posiciones finales` |

Los estilos de esa pantalla están al comienzo del mismo archivo, dentro de `<style>`. Las clases `fq-zone-*` corresponden a zonas; `fq-bracket` y `fq-round`, a cuadros eliminatorios. Antes de cambiar una clase compartida, revisar todos sus usos.

## Distinguir vista acción y regla

| Capa | Archivo de competencia | Responsabilidad |
|---|---|---|
| Vista | [manage-tournament-competition.blade.php](../resources/views/filament/resources/tournaments/pages/manage-tournament-competition.blade.php) | HTML, estilos, pestañas y ubicación visual de acciones |
| Página administrativa | [ManageTournamentCompetition.php](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php) | Formularios, modales, botones y coordinación con Livewire |
| Servicio | [FiveQuillasCompetitionService.php](../app/Services/FiveQuillasCompetitionService.php) | Reglas deportivas, transacciones, resultados, clasificación y propagación |
| Datos | [Modelos](../app/Models) | Relaciones, atributos y estado persistido |

La propiedad `$view` de `ManageTournamentCompetition` indica qué Blade utiliza. `getViewData()` prepara la información que recibe la pantalla. No cambiar una clasificación dentro del HTML: corregir la regla en el servicio y luego revisar sus distintas salidas.

## Acciones de la pantalla de organización

Buscar el nombre del método en [ManageTournamentCompetition.php](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php):

| Acción o problema | Método |
|---|---|
| Crear o editar zonas | `createZonesAction`, `zoneRowsForSlot`, `zoneEntryOptions` |
| Número de sorteo de un clasificado | `zoneDrawNumberAction` |
| Cargar Masters del horario | `loadRegisteredMastersAction` |
| Preparar o actualizar grilla | `preparePhaseAction` |
| Configurar cruces por horario | `configureSlotCrossingsAction` |
| Asignar horarios de un bloque | `schedulePhaseAction` |
| Programar un partido pendiente | `scheduleWaitingMatchAction` |
| Registrar resultado o ganador | `recordResultAction` |
| Reiniciar partida | `resetResultAction` |
| Corregir participantes | `correctMatchParticipantsAction` |
| Llamar a mesa y cancelar llamado | `callMatchAction`, `cancelMatchCallAction` |
| Revisar o guardar puntos | `editFinalScoreAction`, `saveFinalScoringAction` |
| Estado y datos generales mostrados | `getViewData` |

Los formularios de configuración y publicación también se construyen desde esa clase. Si un modal no cierra, revisar su acción, validación y respuesta de Livewire; no sólo el Blade.

## Reglas deportivas en el servicio

| Tema | Métodos de FiveQuillasCompetitionService |
|---|---|
| Inscripciones habilitadas | `syncApprovedEntries`, `refreshEntryPhaseAssignments` |
| Creación de zonas | `createManualZones` |
| Formato y plantillas | `configureFormat`, `applyCompetitionTemplate`, `phaseTemplate` |
| Construcción y límite de rondas | `ensureBracketSkeleton`, `reconcileBracketRounds` |
| Horarios y mesas | `scheduleConfiguredMatches`, `availableTableOptions`, `callMatch` |
| Cruces por horario | `slotEliminationCandidates`, `createManualSlotElimination` |
| Emparejamientos de Nacionales | `skipUnnecessaryCircuitPlayIn`, `circuitCrossingsAreComplete` |
| Ingreso directo de Masters | `loadRegisteredMasters` |
| Siembra y sorteos | `saveBracketParticipantOrder`, `saveDrawNumbers`, `saveQualifiedDrawNumber` |
| Resultados y ausencias | `recordMatchResult`, `declareMatchWinner`, `resolveAbsentMatch`, `resolveBracketByes` |
| Correcciones y reinicio | `correctMatchParticipants`, `resetMatchResult` |
| Estado de fases y pendientes | `completePhaseWhenReady`, `reopenIncompleteConcurrentPhases`, `unassignedCompetitionEntries` |
| Estadísticas de zona | `recalculateGroupStatistics` |
| Clasificados | `qualifiedParticipantRows` |
| Posiciones finales | `recalculateFinalStandings`, `ensureFinalStandingsOrder`, `finalStandingPerformance` |
| Instancia y puntos | `finalInstanceForEntry`, `ruleForEntryPlacement`, `proposedPointsForEntry`, `commitFinalScoring` |

Las tablas de puntuación y su aplicación general están en [TournamentScoringService.php](../app/Services/TournamentScoringService.php). El tipo de torneo define las reglas; el servicio de competencia determina qué regla corresponde a cada participante.

## Cuatro presentaciones diferentes de la competencia

Una corrección administrativa no modifica automáticamente la presentación pública o un PDF. Identificar el destino antes de editar:

| Destino | Vista principal | Entrada o controlador |
|---|---|---|
| Administración | [manage-tournament-competition.blade.php](../resources/views/filament/resources/tournaments/pages/manage-tournament-competition.blade.php) | [ManageTournamentCompetition.php](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php) |
| Resultados públicos | [five-quillas-competition.blade.php](../resources/views/public/five-quillas-competition.blade.php) | [FiveQuillasCompetitionPageController.php](../app/Http/Controllers/FiveQuillasCompetitionPageController.php) |
| Transmisión OBS | [five-quillas-competition-broadcast.blade.php](../resources/views/public/five-quillas-competition-broadcast.blade.php) | [FiveQuillasCompetitionBroadcastController.php](../app/Http/Controllers/FiveQuillasCompetitionBroadcastController.php) |
| PDF por sección | [five-quillas-competition-section.blade.php](../resources/views/pdf/five-quillas-competition-section.blade.php) | [FiveQuillasCompetitionSectionPdfController.php](../app/Http/Controllers/FiveQuillasCompetitionSectionPdfController.php) |
| Información no disponible | [five-quillas-competition-unavailable.blade.php](../resources/views/public/five-quillas-competition-unavailable.blade.php) y [competition-availability.blade.php](../resources/views/public/partials/competition-availability.blade.php) | Controladores de página y transmisión |
| Datos públicos | Sin una vista HTML propia | [FiveQuillasCompetitionPublicController.php](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php) |

### Fragmentos compartidos

Todos están en [partials de organización](../resources/views/filament/resources/tournaments/pages/partials):

- `competition-bracket.blade.php`: cuadro eliminatorio administrativo. No es el dibujo de las zonas.
- `competition-qualified-action.blade.php`: consulta de clasificados.
- `competition-section-tools.blade.php`: herramientas de impresión y compartir de cada sección.

El manejo común de archivos PDF en el navegador está en [competition-pdf-script.blade.php](../resources/views/partials/competition-pdf-script.blade.php). Revisar también el JavaScript de impresión y compartir que permanece en la vista administrativa o pública.

## Cómo encontrar cualquier otra pantalla

Consultar el [catálogo de archivos por módulo](mapa-archivos.md), que enlaza los recursos administrativos, formularios, tablas, páginas, servicios, controladores, modelos, permisos, comandos, vistas y pruebas.

En Filament muchas pantallas no tienen un Blade propio. Un listado suele configurarse con `Tables`, su formulario con `Schemas` y sus botones desde `Pages`. Por ejemplo, para cambiar un campo del jugador se empieza en `Players/Schemas/PlayerForm.php`, no en una supuesta vista `jugadores.blade.php`.

## Recetas de corrección

### Cambiar tamaño de nombres o alineación de sets

1. Determinar si el problema ocurre en administración, público, OBS o PDF.
2. Buscar `fq-match-player` en administración; `.line` y `.set-point` en público; los estilos específicos en transmisión.
3. Cambiar la presentación sin modificar los resultados persistidos.
4. Comprobar nombres largos, sets vacíos, mesa asignada, móvil e impresión cuando corresponda.

### Corregir clasificación puntos o avance de fase

1. Revisar configuración del tipo de torneo y de la fase.
2. Reproducir con datos mínimos: participantes, resultados y clasificados esperados.
3. Corregir la regla en el servicio, preservando el ranking capturado y los resultados históricos.
4. Verificar administración, salida pública y PDF, porque comparten los datos derivados.
5. Agregar una prueba que incluya el caso real, por ejemplo cuadros con LIBRE o ausencia.

### Cambiar nombre logo país moneda o datos del cliente

Editar su `.env` siguiendo [configuración de clientes](clientes.md), no buscar y reemplazar nombres por todo el repositorio. `config/cliente.php` es la estructura central; `FabPath` resuelve los archivos y `Cliente::money()` formatea importes. Regenerar cachés después del cambio.

### Corregir el calendario WordPress

El adaptador mantenido está en [wordpress-calendar.js](../public/js/wordpress-calendar.js), las pruebas en [wordpress-calendar.test.cjs](../tests/JavaScript/wordpress-calendar.test.cjs) y el servidor de datos en [UpcomingTournamentsController.php](../app/Http/Controllers/UpcomingTournamentsController.php). El estado por fechas también depende de `Tournament::calendarStatus()`.

Los respaldos `docs/wordpress/calendario-wpcode-9056.js` y `calendario-filtros.html` corresponden a la integración FAB. El fragmento activo en WordPress puede requerir actualización separada: hacer push de Laravel no reemplaza por sí mismo un fragmento guardado en WordPress.

## Búsqueda dentro de GitHub

1. Abrir el repositorio y seleccionar la rama `main` para ver la última versión publicada.
2. Presionar **T** en la página del repositorio para buscar un archivo por nombre; escribir `manage-tournament-competition` para la vista administrativa.
3. Abrir el archivo y buscar el símbolo o texto de la tabla de esta guía.
4. Para buscar en todo el código, usar consultas como `repo:hgmenna/app_fab_laravel "createZonesAction"` o `repo:hgmenna/app_fab_laravel "fq-zone-head"`.
5. Para registrar una corrección, copiar el enlace del archivo y anotar el método o clase. Los números de línea cambian entre versiones.

## Antes de publicar una modificación

- Confirmar si afecta apariencia, formularios, reglas, datos, permisos o integración externa.
- Trabajar sobre copia local o entorno de prueba y disponer de respaldo para producción.
- Ejecutar pruebas pertinentes. Los archivos de [pruebas](../tests) son la referencia de comportamiento esperado.
- Usar `php artisan view:cache` para compilar plantillas y `npm run build` si cambiaron assets de Vite. El calendario de `public/js` no necesita compilación.
- Registrar commit y actualizar la documentación si se cambia la ubicación o responsabilidad de un archivo.
- Aplicar el [procedimiento de producción](deployment.md). No corregir puntos o clasificados directamente mediante SQL.

Las pruebas de integración con migraciones completas necesitan una base compatible con el proyecto. Las suites aisladas de competencia y configuración permiten comprobar sus casos sin ejecutar todas las migraciones históricas. Los avisos locales por falta de `.env` deben distinguirse de un fallo de aserción.
