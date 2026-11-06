# Instalaciones independientes por cliente

Se comercializa el sistema completo. Cada cliente utiliza su dominio web, subdominio del sistema, base de datos, archivos, claves y credenciales independientes. No hay selección de clientes dentro de una misma aplicación ni restricciones comerciales por módulos.

## Configuración central

`config/cliente.php` define la estructura y los valores compatibles con la Federación Argentina de Billar. Para una nueva instalación se completan las variables de `.env.cliente.example` en su propio `.env`: identidad legal y visible, país, contactos, web, sistema, moneda, separadores, idioma, zona horaria, formato de fecha, logos, favicon y color del membrete. Los datos del proveedor se configuran mediante `PROVEEDOR_*`, separados de los institucionales.

La moneda configura presentación; no convierte saldos ni modifica puntos deportivos. El idioma configura el locale: no traduce automáticamente textos que aún no tengan un catálogo de traducciones. Las denominaciones y reglas deportivas permanecen en los catálogos de la base de datos.

`APP_TIMEZONE=UTC` conserva la interpretación de fechas de almacenamiento del sistema original. `CLIENTE_ZONA_HORARIA` controla las comprobaciones del calendario y las tareas programadas. No cambiar la zona de almacenamiento de una base existente sin analizar previamente sus fechas.

La carpeta publicada se detecta como `../public_html` cuando existe o como `public/` en una instalación convencional. Para otras disposiciones se configura `CLIENTE_RAIZ_PUBLICA` con una ruta absoluta. Logos y pie son rutas relativas a esa carpeta. Las credenciales de base y correo permanecen en `DB_*` y `MAIL_*`; jamás se incorporan al repositorio ni a archivos públicos.

## Actualizar la federación actual

No se modifican registros de federaciones, jugadores, resultados ni saldos. Los valores predeterminados conservan la identidad actual. Confirmar que la carpeta pública detectada sea la utilizada por el hosting; configurar `CLIENTE_RAIZ_PUBLICA` si corresponde. Antes de publicar, disponer de respaldo de base y archivos.

```bash
git pull --ff-only origin main
php artisan optimize:clear
php artisan cliente:verificar --database
php artisan optimize
php artisan queue:restart
```

La verificación no escribe datos ni muestra credenciales. Si falta un logo, una ruta o una configuración válida, informa el campo y termina con error.

## Nuevo cliente

1. Preparar dominio web y subdominio del sistema con HTTPS y carpetas independientes.
2. Instalar el mismo código base y sus dependencias; configurar el servidor web para la carpeta pública del proyecto.
3. Crear una base vacía y un usuario exclusivo; completar `DB_*`, `APP_URL`, correo, sesiones y las variables de `.env.cliente.example`. Usar `APP_ENV=production` y `APP_DEBUG=false`.
4. Generar una `APP_KEY` nueva con `php artisan key:generate`; nunca copiar la clave de otro cliente.
5. Copiar sus logos y pie de documentos; comprobar permisos de `storage`, `bootstrap/cache` y archivos persistentes.
6. Ejecutar las migraciones sobre la base nueva y preparar el almacenamiento público correspondiente. No ejecutar `migrate:fresh` sobre una base existente.
7. Cargar los catálogos y permisos aprobados para ese cliente y crear el administrador. No copiar padrones, comprobantes ni los seeders históricos del cliente anterior. El seeder general de desarrollo no es un instalador comercial.
8. Ejecutar `php artisan cliente:verificar --database`, compilar los assets y regenerar cachés.
9. Configurar worker, scheduler y respaldos independientes; verificar paneles, logotipos, correo, importes, fechas, PDF y resultados públicos.
10. Conectar su WordPress a su propio sistema. Cambiar cliente, datos o servicios requiere actualizar su `.env` y regenerar la caché; no editar clases internas.

## WordPress

El calendario externo `public/js/wordpress-calendar.js` obtiene el dominio del sistema desde la URL del propio script. En WordPress se carga desde el subdominio del cliente:

```html
<script src="https://sistema.SU-DOMINIO/js/wordpress-calendar.js" defer></script>
```

La tabla debe consumir `https://sistema.SU-DOMINIO/torneos.json?estado=todos`. Si su identificador difiere de 64, definir previamente `window.fabCalendarConfig = { selector: 'table.wpDataTableID-XX' }`. No dejar simultáneamente el adaptador nuevo y el fragmento anterior activos. Los archivos `calendario-wpcode-9056.js` y `calendario-filtros.html` son respaldos de la publicación FAB existente, no una plantilla universal para nuevos clientes.

El shortcode de ranking admite `sistema_url`, `federacion` y `titulo` para configurar la instalación destino desde WordPress. Ninguna integración debe consultar el dominio de otro cliente.

## Alcance de esta preparación

Quedan centralizadas identidad, presentación institucional, proveedor, rutas, fechas, zona de calendario y scheduler, moneda y dirección del adaptador público del calendario. No se instala un cliente automáticamente, no se migran datos, no se alteran reglamentos ni se implementan multicliente o licencias por módulos. Las cuentas bancarias, catálogos, permisos, estructura deportiva y contenidos de WordPress se configuran para cada instalación.
