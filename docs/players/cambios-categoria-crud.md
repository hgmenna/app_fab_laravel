# CRUD de cambios de categoría

La pantalla **Jugadores → Cambios de categoría** ofrece Nuevo cambio, Ver, Editar y Eliminar. Las mismas acciones están en el historial de categorías de la ficha del jugador.

## Permisos existentes

No se agregaron permisos nuevos que requieran migraciones o regenerar roles.

- Consultar: permisos de lectura de jugadores.
- Crear: `EditField` y `Update:Player`; se comprueba el jugador elegido y su alcance.
- Editar: `EditField` y la política de actualización del jugador.
- Eliminar: `EditField` y la política de eliminación del jugador.

Se conservan las restricciones por disciplina, club y las reglas específicas de la política del jugador. Ocultar un botón no es la única protección: el servicio vuelve a autorizar la operación al ejecutarla.

## Correcciones aplicadas

Los cambios permanentes manuales o de temporada se pueden corregir y eliminar. Los registros provenientes de torneos, afiliación inicial y ranking continúan disponibles para consulta; no se convierten en cambios permanentes editables desde este formulario.

Editar permite corregir categoría nueva, fecha efectiva, motivo y observaciones. Se validan disciplina y categorías permanentes: M/N temporales del ranking no se permiten como afiliación permanente.

Si un cambio ya estaba aplicado, el servicio recompone la cadena cronológica y la categoría vigente del jugador. Corregir un cambio anterior también recalcula la categoría de origen de los cambios posteriores. Pasar un cambio aplicado a una fecha futura lo deja pendiente y restaura la categoría que corresponde hoy. Eliminar el último cambio restaura la categoría anterior. No se alteran resultados históricos de torneos ni registros del ranking.

Las operaciones bloquean al jugador y sus cambios dentro de una transacción. El programador de cambios pendientes comparte ese bloqueo. Si la categoría actual no coincide con la cadena registrada, se solicita revisar el historial en lugar de sobrescribir un cambio externo inadvertidamente.

## Archivos

- `app/Filament/Actions/PlayerCategoryHistoryActions.php`: formularios y acciones reutilizadas.
- `app/Filament/Resources/Players/Pages/CategoryChangesReport.php`: informe con creación y acciones por registro, acotado al padrón permitido.
- `app/Filament/Resources/Players/RelationManagers/CategoryHistoriesRelationManager.php`: acciones en la ficha.
- `app/Filament/Resources/Players/PlayerResource.php`: el botón original Cambiar categoría usa el mismo servicio autorizado.
- `app/Policies/PlayerCategoryHistoryPolicy.php`: permisos de lectura, creación, modificación y eliminación.
- `app/Services/PlayerCategoryHistoryEditor.php`: validación, transacción, corrección y recomposición de la cadena.
- `app/Services/PlayerCategoryChangeService.php`: creación programada y aplicación de cambios con bloqueo compartido.
- `tests/Feature/PlayerCategoryHistoryEditorTest.php`: CRUD, restauración, cadenas, cambio a fecha futura, categorías inválidas, permisos y apertura/guardado del modal real.

## Actualización en producción

```bash
git pull --ff-only origin main && php artisan optimize:clear
```

No requiere migraciones. Volver a entrar al informe después de actualizar. Si faltan acciones, revisar los permisos existentes del rol en la disciplina y club correspondientes.
