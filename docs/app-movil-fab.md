# FAB en el celular

La aplicación web instalable está en `/app` del mismo servidor HTTPS que usa FAB.
No requiere un usuario nuevo ni una base de datos aparte.

- **Android:** abrí `/app` en Chrome y usá **Instalar aplicación** o **Agregar a pantalla principal**.
- **iPhone:** abrí `/app` en Safari y usá **Compartir → Agregar a pantalla de inicio**.
- **Cuenta y gestión:** ingresá con la cuenta de FAB. Las pantallas se abren en los paneles existentes y el servidor vuelve a comprobar los permisos de la operación.
- **Prueba antes de publicar:** verificá los filtros del calendario, la inscripción y los resultados con un invitado. Con un organizador, probá la creación y la organización de un torneo de prueba. Repetí con un usuario sin permisos y comprobá que el acceso directo a una ruta protegida se rechace.
- **Distribución:** esta entrega se instala desde el navegador. Los paquetes de Google Play y App Store se preparan y prueban por separado.

La portada necesita conexión para mostrar datos vigentes. El aviso sin conexión no guarda calendario ni sesiones.

## APK Android para pruebas en Windows

El proyecto Android está en `mobile/android`. Compilá `:app:assembleDebug` con Gradle 8.13, JDK 17 y Android SDK 36. La APK de prueba se conecta al simulador local. Con el servidor de la vista previa activo en el puerto 8017 y el equipo Android conectado por USB, ejecutá:

```powershell
adb reverse tcp:8017 tcp:8017
adb install -r ruta/al/instalador.apk
```

Luego abrí **FAB Deportes · Prueba**. La compilación definitiva usa HTTPS y apunta a la ruta móvil de FAB. Para distribuirla se requiere desplegar ese servidor y firmar la APK de producción.

## Entrega firmada 1.0.0

Los instaladores se encuentran en `mobile-dist/FAB-Deportes-1.0.0.apk` y `mobile-dist/FAB-Deportes-1.0.0.aab`.
La APK usa la clave de distribución y el AAB usa una clave de subida independiente. Los almacenes y sus contraseñas protegidas con DPAPI se guardan en `mobile-signing`, excluido de Git. Conservarlos y respaldarlos es necesario para futuras actualizaciones. Los archivos DPAPI solo se descifran con la cuenta de Windows que los creó.

La edición de producción abre `https://sistem.federacionargentinadebillar.org/app`. Antes de compartir el instalador, se debe desplegar la parte móvil del servidor y probar el ingreso, los permisos, la inscripción y los cupos con cuentas reales. La organización móvil aún permite consultar fases y partidos; sus acciones de edición y el flujo de pagos siguen pendientes.

Para Play Console se usa el AAB firmado, junto con la ficha, política de privacidad, declaración de seguridad de datos y las pruebas exigidas por la cuenta. La compilación y la firma no equivalen a aprobación de Google.
