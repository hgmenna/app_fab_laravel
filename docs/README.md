# Documentación técnica

Actualizada el 23 de octubre de 2026.

## Orden de lectura

1. [Arquitectura](architecture.md).
2. [Modelo de dominio](domain-model.md).
3. [Torneos y competencia](tournaments.md).
4. [Módulos](modules.md).
5. [Referencia técnica](function-reference.md).
6. [Desarrollo](development.md).
7. [Producción](deployment.md).

## Principios

- `/admin` es la interfaz autenticada; `/invitado` y las páginas públicas son de consulta.
- La inscripción es la fuente administrativa; la entrada de competencia conserva los datos deportivos.
- Ranking, categoría y posición históricos se guardan. Un torneo antiguo no se reinterpreta con valores actuales.
- Cada fase define origen, sets, puntos por set, horarios, clasificados y ordenamiento.
- Una corrección recalcula sólo la información derivada que depende de ella.
- Publicar, cobrar, clasificar y acreditar puntos son operaciones distintas y trazables.

## Mapa del código

```text
app/Filament/Resources     CRUD administrativo
app/Filament/Pages         flujos especiales
app/Http/Controllers       páginas públicas, JSON y PDF
app/Models                  entidades Eloquent
app/Services                reglas y transacciones
app/Console/Commands        procesos manuales/programados
app/Policies                autorización
resources/views             vistas y documentos
routes                      rutas y calendario
database/migrations         esquema
tests                       pruebas críticas
```

Antes de cambiar una función, identifique servicio, modelos, acción Filament, salida pública y pruebas. La competencia normalmente afecta administración, vista pública, impresión y datos derivados.
