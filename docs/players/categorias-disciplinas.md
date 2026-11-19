# Categorías y disciplinas: prevención y reparación

Los códigos no son globalmente únicos. Por ejemplo, `M` puede significar Master en cinco quillas y Máxima en otra disciplina. El historial debe resolver siempre el código dentro de la disciplina del jugador.

## Causa corregida

`RankingService::syncGeneralRanking`, la determinación de ascensos de temporada y el comando de baseline consultaban categorías sin limitar su disciplina. Al indexar por código, una categoría de otra disciplina podía reemplazar a la correcta.

Se limita el catálogo por disciplina y se agregan validaciones al guardar historiales y promociones pendientes. Los cambios programados también se validan antes de aplicarlos. La lectura de categoría efectiva deja de considerar categorías históricas de otras disciplinas; una categoría Máxima de otra modalidad no se considera Master de cinco quillas.

## Diagnóstico en producción

```bash
git pull --ff-only origin main && php artisan optimize:clear
php artisan player-categories:audit-disciplines
```

El diagnóstico no escribe datos. Muestra jugador, disciplina, categoría anterior, categoría nueva y origen.

## Reparación automática de errores de ranking

```bash
php artisan player-categories:audit-disciplines --repair-ranking
```

Antes de cambiar registros se genera un JSON recuperable en `storage/app/private/repairs/`. El comando imprime la ruta exacta. El respaldo incluye los atributos originales, los identificadores y el plan de corrección.

Solo se reparan registros cuyo origen es `ranking` cuando el código de cada extremo tiene una única equivalencia dentro de la disciplina del jugador. No se adivinan categorías por nombre.

- Un destino incorrecto Máxima (`M` de otra disciplina) se reemplaza por Master (`M` de cinco quillas).
- Si al corregir los identificadores queda Master → Master, la fila representa un cambio que nunca existió: se elimina esa fila errónea, conservando el registro original en el respaldo.
- Si un cambio posterior tiene origen Máxima de otra disciplina y destino Nacional correcto, se corrige el origen a Master.
- Los registros manuales, de temporada, incompletos o ambiguos se informan para revisión individual y no se modifican automáticamente.

El comando no cambia la categoría permanente del jugador ni recalcula puntajes o torneos. Las correcciones se ejecutan en una transacción, con bloqueo y verificación de que el historial no cambió desde el diagnóstico.

## Archivos

- `app/Services/PlayerCategoryDiscipline.php`: catálogo acotado por disciplina e invariante común.
- `app/Models/PlayerCategoryHistory.php`: validación al guardar historial.
- `app/Models/PlayerCategoryPromotion.php`: validación de ascensos pendientes.
- `app/Services/RankingService.php`: resolución correcta de códigos en el Ranking General.
- `app/Services/PlayerCategoryPromotionService.php`: determinación y aplicación de ascensos sin cruzar disciplinas.
- `app/Console/Commands/SeedRankingCategoryBaseline.php`: categorías M/N de cinco quillas.
- `app/Services/PlayerCategoryChangeService.php`: comprobación al programar/aplicar cambios antiguos.
- `app/Services/PlayerCategoryHistoryService.php`: categoría efectiva y conjunto temporal M/N sin categorías ajenas.
- `app/Console/Commands/AuditPlayerCategoryDisciplines.php`: auditoría, respaldo y reparación explícita.
- `tests/Feature/PlayerCategoryDisciplineTest.php`: códigos duplicados entre disciplinas, bloqueo, datos antiguos y reparación recuperable.

No hay migraciones nuevas ni reparación automática al hacer pull: la revisión y reparación se ejecutan explícitamente con los comandos anteriores.
