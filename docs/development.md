# Desarrollo

## Requisitos

- PHP 8.2 o superior con extensiones requeridas por Laravel y DOMPDF.
- Composer.
- Node.js compatible con Vite 7 y npm.
- MySQL.

## Preparación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

Configure `APP_URL`, locale, base de datos, correo, disco, caché, sesiones y cola. En desarrollo puede usar `php artisan serve` y `npm run dev`.

## Pruebas

```bash
php artisan test
php artisan test --filter=FiveQuillasNationalBracketTest
php artisan test --filter=TournamentScoringServiceTest
```

Las pruebas actuales cubren, entre otros temas, llaves nacionales, puntuación, reglamentos, disponibilidad, parejas, afiliaciones, membresías, permisos y rankings.

Una corrección visual reversible no necesita una prueba artificial. Una regla de negocio, cálculo, idempotencia o propagación sí.

## Flujo de cambio

1. Reproducir con datos mínimos y registrar estado anterior.
2. Ubicar servicio, modelos, página/controlador y vistas.
3. Confirmar quién es dueño del dato y cuáles son sus derivados.
4. Cambiar primero la regla central.
5. Adaptar administración, público e impresión.
6. Agregar o ajustar una prueba que represente el caso real.
7. Ejecutar prueba focalizada, suite y build.
8. Revisar migraciones, permisos, scheduler y caché.

## Convenciones críticas

- Fechas de competencia se interpretan en `America/Argentina/Buenos_Aires`.
- Horarios y pestañas se ordenan por fecha/hora real, no por etiqueta.
- Ranking y categoría históricos se capturan, no se consultan dinámicamente para torneos pasados.
- Use relaciones Eloquent y claves foráneas; evite nombres como identidad.
- Use transacciones para preparar fases, corregir llaves, propagar ganadores y acreditar puntos.
- Las operaciones repetibles deben ser idempotentes.
- La vista pública no muestra detalles de implementación.

## Livewire y Filament

- Conserve claves `wire:key` estables.
- No cambie dinámicamente la ruta de estado de un campo montado.
- En repetidores, normalice índices antes de guardar.
- Una opción seleccionada debe excluirse de selectores hermanos sin desaparecer del propio campo.
- Después de actualizar opciones, compruebe selección con mouse y teclado.
- Una acción modal debe notificar errores de validación y cerrarse al guardar correctamente.

## Migraciones

Las migraciones son acumulativas. No edite una migración ya desplegada para corregir producción; cree una nueva. Una migración de datos debe ser reejecutable o detectar datos ya procesados.

Antes de agregar una columna histórica determine cómo completar registros existentes y cómo distinguir “desconocido” de cero.

## Diagnóstico

```bash
php artisan about
php artisan route:list
php artisan schedule:list
php artisan queue:failed
php artisan optimize:clear
tail -n 200 storage/logs/laravel.log
```

Ante un 500 de Livewire revise primero la respuesta de `/livewire/update` y luego el log. Los avisos de carga diferida de imágenes del navegador no son por sí solos errores funcionales.

## Revisión de una competencia

Verifique al menos:

- participantes y horarios de ingreso;
- tamaño de llave y cantidad final de clasificados;
- siembra y lugares LIBRE;
- propagación después de corregir un partido;
- ganador manual y sets computados;
- orden de tablas y clasificados;
- vista administrativa, pública, móvil y PDF;
- campeón sólo al finalizar;
- acreditación de puntos sin duplicados.
