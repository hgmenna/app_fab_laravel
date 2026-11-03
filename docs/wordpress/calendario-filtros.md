# Filtros del calendario deportivo

El código mantenido está en `public/js/wordpress-calendar.js`. Se publica al actualizar Laravel; no requiere compilar assets.

La instalación actual está en WPCode, fragmento **9056: Iconos booleanos de torneos**. Se actualizó directamente en WordPress el 2 de noviembre de 2026, conservando su presentación anterior. El respaldo exacto publicado está en `calendario-wpcode-9056.js`; se puede pegar en el editor JavaScript de ese fragmento sin etiquetas script. Incluye el mismo filtrado previo a paginar y orden por fechas ISO. La tabla tiene el ordenamiento global deshabilitado: el adaptador lo habilita para aplicar el orden elegido. El selector debe ser `table.wpDataTableID-64`, porque el contenedor también tiene esa clase.

En WordPress, reemplazar el bloque JavaScript anterior que contiene `const tablaSelector='.wpDataTableID-64'` por el contenido de `calendario-filtros.html` (en un bloque HTML). Si el editor acepta JavaScript sin etiquetas, usar el contenido del archivo JS en su lugar. No dejar ambas versiones activas. Mantener el monitor de actualización del calendario, que es un bloque distinto.

Purgar la caché de WordPress/LiteSpeed después de guardar. El script espera la inicialización de wpDataTables, filtra todos sus registros antes de paginar y agrupar, y conserva los enlaces de inscripción, mapas, flyers y resultados.

## Verificación

- Próximos, En curso, Finalizados y Todos muestran sólo el estado elegido.
- Finalizados ordena por fecha de fin descendente, incluso al cambiar de mes o año. Los demás estados ordenan por inicio ascendente.
- Buscar funciona en todas las páginas, ignora tildes y permite combinar palabras del torneo y la sede.
- Desde/Hasta incluye torneos que se superponen con el intervalo, incluso si cruzan de mes. Un intervalo invertido muestra una explicación y ningún torneo.
- Al no coincidir ningún torneo, se muestra el mensaje correspondiente sin meses vacíos.
- Cambiar cualquier filtro vuelve a la primera página; Limpiar filtros restaura Próximos y vacía búsqueda y fechas.
- Comprobar que la página siguiente no recupera torneos excluidos ni meses vacíos.

Pruebas locales: `node --test tests/JavaScript/wordpress-calendar.test.cjs`.
