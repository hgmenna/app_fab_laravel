# PWA FAB Movil — instalación y distribución

## Publicación

Versión de interfaz: 1.0.7. No requiere reconstruir ni reinstalar la APK existente.

En PuTTY, dentro de `app-production`:

```bash
git pull --ff-only origin main && php artisan optimize:clear
```

El archivo de instalación es un enlace web, no un paquete APK:

https://sistem.federacionargentinadebillar.org/app/instalar

La página permite instalar cuando el navegador ofrece esa opción, consultar instrucciones según el dispositivo, compartir/copiar el enlace y descargar el QR. No requiere iniciar sesión. Instalar la PWA no modifica las cuentas ni los permisos existentes.

## Pasos para los usuarios

### Android

1. Abrir el enlace en Chrome. Si se abre dentro de WhatsApp, elegir abrir en el navegador.
2. Tocar **Instalar FAB Movil** y confirmar.
3. Si el botón no aparece, usar el menú de Chrome: **Instalar aplicación** o **Agregar a pantalla principal → Instalar**, según la versión.
4. Abrir FAB desde su icono. Iniciar sesión para las funciones administrativas autorizadas.

### iPhone y iPad

1. Abrir el enlace en Safari.
2. Elegir **Compartir → Agregar a pantalla de inicio**.
3. Si aparece **Abrir como app web**, mantenerlo activado y confirmar con **Agregar**.
4. Abrir FAB desde el icono de inicio. Puede ser necesario iniciar sesión en esta instalación.

### Windows

1. Abrir el enlace en Chrome o Microsoft Edge.
2. Elegir el botón **Instalar FAB Movil** o el icono de instalación en la barra de direcciones.
3. En Edge también está disponible **Menú → Aplicaciones → Instalar este sitio como una aplicación**.

No hace falta una cuenta de Play Store o App Store para instalar desde el navegador. La PWA requiere un navegador compatible; no reemplaza la APK en tablets que carecen de navegador.

## Código QR

Archivo compartible: `public/fab-mobile/install-qr.png`.
URL pública: https://sistem.federacionargentinadebillar.org/fab-mobile/install-qr.png

Es un PNG real de 328 × 328. Codifica la URL de instalación de producción y fue verificado mediante decodificación. Si cambia el dominio, regenerar el PNG con la biblioteca ya incluida `chillerlan/php-qrcode`, usando `QROutputInterface::GDIMAGE_PNG`, `outputBase64=false`, `scale=8` e `imageTransparent=false`. No usa servicios externos.

## Dónde modificar

- `public/fab-mobile/manifest.webmanifest`: identidad estable `/app`, nombre, iconos, colores y alcance de navegación.
- `resources/views/partials/mobile-pwa-head.blade.php`: metadatos y registro de instalación compartidos por todas las vistas móviles.
- `resources/views/mobile-install.blade.php`: página pública con instrucciones, compartir enlace y QR.
- `public/fab-mobile/install.js`: aviso de instalación del navegador, detección de uso instalado, instrucciones por plataforma y acciones de compartir/copiar.
- `public/fab-mobile-worker.js`: pantalla de recuperación sin red para todas las rutas móviles. No intercepta envíos POST ni solicitudes de datos.
- `app/Http/Controllers/MobileAssetController.php`: lista permitida de archivos, necesaria cuando Hostinger separa `public_html` de la carpeta Laravel.
- `routes/web.php`: ruta nombrada `mobile-app.install` en `/app/instalar`.
- `public/fab-mobile/app.css`: estilos de la página y aviso de instalación.

## Conexión y actualizaciones

La PWA consulta al servidor para calendario, resultados, sesión, inscripciones y administración. No guarda páginas privadas, puntajes o formularios en una caché sin conexión. Si no hay red, muestra una pantalla para reintentar; no simula escrituras exitosas.

Las mejoras se reciben al volver a abrir las páginas conectadas. El navegador controla cuándo actualiza nombre e iconos de una instalación existente. La disponibilidad del botón de instalación depende del navegador; iOS utiliza la instalación manual desde Safari.

## Verificaciones

- `tests/Feature/MobilePwaInstallationTest.php`: acceso sin cuenta/base deportiva, archivos servidos por Laravel, identidad y alcance del manifiesto, tamaños de iconos y lectura del QR.
- `tests/JavaScript/mobile-pwa.test.cjs`: flujo de instalación, confirmación real `appinstalled`, instrucciones para iPad, alternativa al portapapeles, recuperación sin red y conservación de las solicitudes de escritura/datos.
- Chrome local: manifiesto válido, cero errores de instalabilidad, service worker activado y página sin desbordamiento horizontal a 390 y 1280 de ancho. No se validó en un dispositivo iOS físico.
