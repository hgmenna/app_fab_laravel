# Sistema de Gestión de la Federación Argentina de Billar

Aplicación web para administrar instituciones, jugadores, afiliaciones, torneos, inscripciones, pagos, rankings y competencias deportivas.

## Tecnología

- PHP 8.2+, Laravel 12, Filament 4 y Livewire.
- MySQL, Tailwind CSS 4 y Vite 7.
- Spatie Permission, Filament Shield y DOMPDF.
- Pest 4 para pruebas automatizadas.

## Documentación

Comience en la [guía para encontrar y modificar cada funcionalidad](docs/guia-mantenimiento.md).

### Dónde está la vista administrativa de las zonas

Está dentro de [manage-tournament-competition.blade.php](resources/views/filament/resources/tournaments/pages/manage-tournament-competition.blade.php). Buscar `$zonePhase`, `fq-zone-bracket` y `fq-standings`. No existe un archivo independiente llamado `zonas.blade.php`.

Los botones y modales están en [ManageTournamentCompetition.php](app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php); los cálculos y reglas están en [FiveQuillasCompetitionService.php](app/Services/FiveQuillasCompetitionService.php). La [guía de mantenimiento](docs/guia-mantenimiento.md) explica esta separación y los archivos de público, OBS e impresión.

### Marcadores de mesa

Configurar `MARKER_SERVER_URL` con la dirección del servidor del marcador y ejecutar `php artisan migrate`. En producción la dirección debe usar HTTPS. En la organización de un torneo de cinco quillas, pulsar **Vincular marcadores** e ingresar el código de cliente, usuario y contraseña del club. El acceso se guarda cifrado por torneo y vence a los siete días.

Para enviar una partida, **llamarla**, asignar una mesa y pulsar **Enviar a tablet**. Con el marcador vinculado, FAB ofrece las mesas libres con tablet habilitada y usa la cantidad de mesas con tablet para programar los horarios. La tarjeta del partido muestra el tanteador del set en curso y los sets ganados. Con la organización abierta, FAB consulta los marcadores cada dos segundos e importa los sets cuando termina el partido. Los resultados editados en FAB después del envío requieren revisión antes de importarse.

Para probar FAB en producción con el marcador en una PC, iniciar el puente local. En **Organización** del torneo, un superadministrador puede pulsar **Habilitar prueba de marcadores** e ingresar la dirección HTTPS y la clave temporal del puente. La conexión se verifica antes de mostrar **Vincular marcadores**. La prueba dura cuatro horas y se puede cerrar desde **Detener prueba de marcadores**.

### Navegación para mantenimiento

- [Mapa de archivos por módulo](docs/mapa-archivos.md): formularios, tablas, páginas, vistas, servicios, modelos, permisos, comandos y pruebas.
- [Referencia de métodos y acciones](docs/referencia-metodos.md): definiciones con enlaces al código y explicaciones disponibles.
- [Criterios de documentación y cambios seguros](docs/criterios-mantenimiento.md): responsabilidades, contratos y estado de la documentación.
- [Índice general de documentación](docs/README.md).

| Documento | Contenido |
|---|---|
| [Configuración de clientes](docs/clientes.md) | Identidad, logos, moneda, dominios e instalaciones independientes. |
| [Arquitectura](docs/architecture.md) | Capas, paneles, rutas y seguridad. |
| [Modelo de dominio](docs/domain-model.md) | Entidades, relaciones y propiedad de datos. |
| [Torneos y competencia](docs/tournaments.md) | Inscripciones, fases, zonas, cruces, resultados y publicación. |
| [Módulos](docs/modules.md) | Instituciones, membresías, tesorería, rankings y reglamentos. |
| [Referencia técnica](docs/function-reference.md) | Servicios, controladores y comandos. |
| [Desarrollo](docs/development.md) | Instalación, pruebas y criterios de cambio. |
| [Producción](docs/deployment.md) | Despliegue, scheduler, colas y recuperación. |

## Inicio local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Para desarrollar la interfaz use `npm run dev`. Configure base, correo, almacenamiento y colas en `.env`.

```bash
php artisan test
npm run build
php artisan route:list
```

No corrija datos deportivos directamente en la base. Los cambios de participantes, resultados, clasificaciones y puntos deben pasar por las acciones y servicios de dominio.
