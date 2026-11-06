# Criterios de documentación y mantenimiento seguro

## Condición de esta entrega

El sistema en funcionamiento es la referencia. Esta entrega modifica únicamente Markdown: no cambia código PHP, Blade, JavaScript, CSS, configuración, base de datos ni procesos. No reorganiza físicamente los archivos ejecutables. El mapa describe su ubicación real actual, incluso cuando una pantalla contiene varios bloques.

Separar archivos o extraer funciones sería una refactorización de código, aunque se pretenda conservar el comportamiento. Requiere un trabajo posterior expresamente autorizado, en una copia y con comprobación de equivalencia. No es un requisito para consultar esta documentación.

## Documentos y propósito

| Necesidad | Documento |
|---|---|
| Encontrar dónde se muestra una funcionalidad o se ejecuta una acción | [Guía de mantenimiento](guia-mantenimiento.md) |
| Encontrar el formulario tabla página servicio o controlador de un módulo | [Mapa de archivos](mapa-archivos.md) |
| Buscar una función con nombre o una acción interna | [Referencia de métodos y acciones](referencia-metodos.md) |
| Entender el recorrido de un torneo | [Torneos y competencia](tournaments.md) |
| Entender la responsabilidad de cada capa | [Arquitectura](architecture.md) |
| Entender entidades y fuente de los datos | [Modelo de dominio](domain-model.md) |
| Instalar y personalizar otro cliente | [Clientes](clientes.md) |
| Actualizar una instalación | [Producción](deployment.md) |

## Cómo documentar una función sin alterar su funcionamiento

La explicación debe permitir entender qué resuelve y qué no hace. Para una regla deportiva o proceso administrativo, registrar:

1. Propósito y caso de uso que la invoca.
2. Entradas y significado de identificadores, colecciones y opciones.
3. Resultado que devuelve y cómo lo utiliza el llamador.
4. Precondiciones: permisos, estados y configuración exigidos.
5. Efectos: registros escritos, estadísticas recalculadas, propagaciones, archivos, avisos y correos.
6. Repetición y transacción: si puede ejecutarse otra vez y qué protege de duplicados.
7. Errores previstos y mensaje mostrado al usuario.
8. Dependencias relevantes y pruebas del comportamiento.

No describir sólo el nombre del método ni inventar garantías a partir de él. Cuando estos puntos no estén escritos o verificados, deben señalarse como pendientes de revisión, no darse por confirmados.

## Cómo documentar una acción administrativa

Una acción Filament reúne varios comportamientos. El nombre visible puede diferir de su identificador y del método PHP que la construye. Documentar:

- Recurso y página que la muestran.
- Etiqueta e identificador interno de la acción.
- Argumentos que recibe desde la vista, por ejemplo `phaseId`, `slotId` o `matchId`.
- Campos del modal, valores iniciales y validaciones.
- Método de servicio invocado al confirmar.
- Condición para estar visible o habilitada.
- Comportamiento al guardar, cancelar o fallar.

Ejemplo existente: `schedulePhaseAction()` está en `ManageTournamentCompetition.php`; construye el modal de asignación horaria y llama a `scheduleConfiguredMatches()` en el servicio. El Blade coloca el botón. No son tres implementaciones distintas de la programación: son interfaz, coordinación y regla.

## Organización que se debe preservar

- Los formularios y vistas recopilan y muestran datos; las reglas de negocio pertenecen a los servicios.
- Los modelos representan entidades, relaciones y consultas comunes.
- Los controladores entregan páginas, datos públicos o documentos; no deben inventar una clasificación diferente de administración.
- Las Policies y los permisos controlan acceso; esconder un botón no reemplaza autorización.
- Las notificaciones, correos, colas y scheduler también forman parte de los efectos de una operación.
- Los datos de cliente se mantienen en la configuración central y su `.env`, no mediante reemplazos de nombres en múltiples archivos.
- Una extracción futura debe mantener nombres de acciones, claves Livewire, alcance de variables, eventos, orden de estilos y contratos de datos.

## Estado real de cobertura

El inventario enlaza todos los métodos con nombre reconocidos en las clases PHP de `app` y las declaraciones de acciones con identificadores literales. Extrae los PHPDoc existentes y describe los métodos de estructura habituales de Laravel, Filament y Eloquent. Los métodos sin explicación específica están identificados como tales.

Esto no equivale a una revisión semántica exhaustiva de cada línea. Los callbacks anónimos se consultan dentro de su método contenedor, los métodos heredados están en sus dependencias y las funciones JavaScript incluidas en Blade se consultan desde la vista enlazada. El documento de referencia indica la cobertura y las descripciones específicas pendientes, sin ocultarlas.

Para completar contratos detallados sin riesgo, la siguiente revisión puede hacerse por módulo, sólo leyendo el código y ampliando Markdown: competencia; puntuación y rankings; inscripciones; tesorería; reglamentos; usuarios y permisos; publicación y documentos; configuración y procesos. No requiere modificar ni desplegar el sistema para agregar esas explicaciones.

## Mantener la documentación actualizada

Cuando se autorice un cambio funcional, incluir en el mismo cambio las correcciones de documentación necesarias. Si se mueve o renombra un método, actualizar sus enlaces. Si se agrega una acción, documentar su recorrido completo. Registrar el caso de prueba y el efecto esperado. Los números de línea son ayudas de navegación y pueden cambiar; el nombre del archivo y del método siguen siendo la referencia principal.

Una actualización que sólo modifica Markdown no necesita migraciones, reconstrucción de assets, reinicio de colas ni limpieza de caché de producción. Puede consultarse directamente en GitHub.
