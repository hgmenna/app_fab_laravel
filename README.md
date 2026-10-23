# Sistema de Gestión de la Federación Argentina de Billar

Aplicación web para administrar instituciones, jugadores, afiliaciones, torneos, inscripciones, pagos, rankings y competencias deportivas.

## Tecnología

- PHP 8.2+, Laravel 12, Filament 4 y Livewire.
- MySQL, Tailwind CSS 4 y Vite 7.
- Spatie Permission, Filament Shield y DOMPDF.
- Pest 4 para pruebas automatizadas.

## Documentación

Comience en [docs/README.md](docs/README.md).

| Documento | Contenido |
|---|---|
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
