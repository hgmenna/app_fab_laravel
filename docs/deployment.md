# Producción y operación

## Preparación previa

1. Confirmar copia reciente de base y archivos subidos.
2. Revisar migraciones y variables nuevas.
3. Ejecutar pruebas y build en el código que se publicará.
4. Anotar el commit actual para recuperación.

## Actualización estándar

Los comandos parten del directorio de la aplicación:

```bash
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

Si el cambio sólo modifica documentación, basta con:

```bash
git pull --ff-only origin main
```

## Scheduler

El servidor debe ejecutar cada minuto:

```cron
* * * * * php artisan schedule:run >> /dev/null 2>&1
```

Laravel dispara promociones 00:05, cambios de categoría 00:10, vencimientos de membresía 00:15 y transferencias 00:20, con zona horaria argentina y sin superposición.

Compruebe con:

```bash
php artisan schedule:list
```

## Cola

Con `QUEUE_CONNECTION=database` debe existir un worker supervisado. Después de cada despliegue se ejecuta `php artisan queue:restart`. Revise fallos con:

```bash
php artisan queue:failed
```

## Almacenamiento y permisos

`storage` y `bootstrap/cache` deben ser escribibles por PHP. El enlace público se crea una vez con:

```bash
php artisan storage:link
```

La copia de seguridad incluye base de datos y el contenido persistente de `storage/app`.

## Verificación posterior

1. Abrir `/up`.
2. Iniciar sesión en `/admin`.
3. Abrir `/invitado` y un torneo público.
4. Revisar logos/fotos y descargar un PDF.
5. Probar una acción Livewire de bajo impacto.
6. Revisar `storage/logs/laravel.log` y trabajos fallidos.
7. Confirmar el commit con `git rev-parse --short HEAD`.

## Recuperación

La recuperación debe preservar datos creados después del despliegue. No use `migrate:rollback` sin analizar si eliminará información. Procedimiento recomendado:

1. activar mantenimiento si el incidente afecta escrituras;
2. restaurar el commit anterior;
3. reinstalar dependencias/build del commit;
4. limpiar y regenerar cachés;
5. aplicar una migración correctiva si el esquema ya recibió datos;
6. reabrir y verificar.

## Variables sensibles

Nunca versionar `.env`, claves, contraseñas, cookies, tokens ni datos de comprobantes. En producción use `APP_DEBUG=false`, correo real, URL HTTPS y permisos mínimos de base y sistema de archivos.

## Monitoreo operativo

- espacio disponible para logs y archivos;
- errores 500 y respuestas de Livewire;
- cola y scheduler;
- órdenes y membresías vencidas;
- entrega de correo;
- integridad de archivos públicos;
- duración de consultas de rankings y competencias grandes.
