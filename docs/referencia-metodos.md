# Referencia de métodos y acciones

Actualizada el 6 de noviembre de 2026. Inventario de métodos PHP con nombre e identificadores de acciones Filament declarados en `app`. Los enlaces apuntan a su definición. No incluye como funciones independientes las closures anónimas: se consultan dentro del método o acción que las contiene. Los scripts y vistas están enlazados en el [mapa de archivos](mapa-archivos.md).

Las explicaciones proceden del PHPDoc existente o de responsabilidades reconocibles de Filament Laravel y Eloquent. Cuando no hay un contrato escrito se señala expresamente y se muestra la dependencia detectada; no se atribuyen garantías que el código no documenta. La guía funcional está en [guia-mantenimiento.md](guia-mantenimiento.md).

Cobertura del inventario: **1044 métodos con nombre y 74 declaraciones de acciones con identificador literal**, distribuidos en 252 archivos PHP. Hay 237 métodos cuya descripción específica no está escrita en PHPDoc y que requieren revisión semántica antes de documentar un contrato exhaustivo. Los métodos heredados de dependencias no se duplican aquí.

## Índice por área

- [Auth](#auth)
- [Console](#console)
- [Exceptions](#exceptions)
- [Filament](#filament)
- [Helpers](#helpers)
- [Http](#http)
- [Imports](#imports)
- [Jobs](#jobs)
- [Mail](#mail)
- [Models](#models)
- [Notifications](#notifications)
- [Policies](#policies)
- [Providers](#providers)
- [Services](#services)

## Auth

### Auth/GuestUser.php

[Abrir archivo completo](../app/Auth/GuestUser.php)

- **[__construct](../app/Auth/GuestUser.php#L15)** — Constructor de la clase; sus parámetros y operaciones de inicialización se encuentran en el enlace.
  Firma: `public function __construct()`
- **[getAuthIdentifier](../app/Auth/GuestUser.php#L31)** — ID virtual para que Filament/Livewire no rompan.
  Firma: `public function getAuthIdentifier()`
- **[getAuthIdentifierName](../app/Auth/GuestUser.php#L36)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function getAuthIdentifierName()`


## Console

### Console/Commands/ApplyDueCategoryPromotions.php

[Abrir archivo completo](../app/Console/Commands/ApplyDueCategoryPromotions.php)

- **[handle](../app/Console/Commands/ApplyDueCategoryPromotions.php#L28)** — Aplica los ascensos de categoría cuya fecha efectiva ya llegó.
  Firma: `public function handle(): int`

### Console/Commands/ApplyDuePlayerCategoryChanges.php

[Abrir archivo completo](../app/Console/Commands/ApplyDuePlayerCategoryChanges.php)

- **[handle](../app/Console/Commands/ApplyDuePlayerCategoryChanges.php#L27)** — Aplica los cambios manuales de categoría cuya fecha efectiva ya fue alcanzada.
  Firma: `public function handle(): int`

### Console/Commands/CloseSeasonRanking.php

[Abrir archivo completo](../app/Console/Commands/CloseSeasonRanking.php)

- **[handle](../app/Console/Commands/CloseSeasonRanking.php#L28)** — Guarda el Ranking Histórico de una temporada.
  Firma: `public function handle(): int`

### Console/Commands/DetermineCategoryPromotions.php

[Abrir archivo completo](../app/Console/Commands/DetermineCategoryPromotions.php)

- **[handle](../app/Console/Commands/DetermineCategoryPromotions.php#L28)** — Determina los ascensos de categoría a partir del RankingHistory de una temporada cerrada.
  Firma: `public function handle(): int`

### Console/Commands/ExpireAnnualMemberships.php

[Abrir archivo completo](../app/Console/Commands/ExpireAnnualMemberships.php)

- **[handle](../app/Console/Commands/ExpireAnnualMemberships.php#L14)** — Inhabilita jugadores que no renovaron su membresía antes del cierre de su disciplina.
  Firma: `public function handle(AnnualMembershipService $service): int`

### Console/Commands/ExpireBankTransferOrders.php

[Abrir archivo completo](../app/Console/Commands/ExpireBankTransferOrders.php)

- **[handle](../app/Console/Commands/ExpireBankTransferOrders.php#L14)** — Marca como vencidas las órdenes de transferencia que no presentaron comprobante.
  Firma: `public function handle(): int`

### Console/Commands/GeocodeClubs.php

[Abrir archivo completo](../app/Console/Commands/GeocodeClubs.php)

- **[handle](../app/Console/Commands/GeocodeClubs.php#L15)** — Geocodifica clubes con relaciones profundas (ciudad/estado/país).
  Firma: `public function handle()`

### Console/Commands/ImportRankingHistoryCommand.php

[Abrir archivo completo](../app/Console/Commands/ImportRankingHistoryCommand.php)

- **[handle](../app/Console/Commands/ImportRankingHistoryCommand.php#L20)** — Importa un Ranking Histórico desde Excel.
  Firma: `public function handle()`
- **[guardarHistorial](../app/Console/Commands/ImportRankingHistoryCommand.php#L627)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `create`, `numero`.
  Firma: `private function guardarHistorial( Player $player, array $row, int $season ): void`
- **[numero](../app/Console/Commands/ImportRankingHistoryCommand.php#L738)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function numero($value)`
- **[normalize](../app/Console/Commands/ImportRankingHistoryCommand.php#L768)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function normalize( string $text ): string`

### Console/Commands/RecalculateRanking.php

[Abrir archivo completo](../app/Console/Commands/RecalculateRanking.php)

- **[handle](../app/Console/Commands/RecalculateRanking.php#L13)** — Recalcula el ranking nacional de 5 Quillas.
  Firma: `public function handle(Ranking5QuillasService $service)`

### Console/Commands/SeedRankingCategoryBaseline.php

[Abrir archivo completo](../app/Console/Commands/SeedRankingCategoryBaseline.php)

- **[handle](../app/Console/Commands/SeedRankingCategoryBaseline.php#L32)** — Inicializa el estado base de categorías temporales M/N del Ranking General para una temporada.
  Firma: `public function handle(): int`

### Console/Commands/SyncTournamentScoringAssignments.php

[Abrir archivo completo](../app/Console/Commands/SyncTournamentScoringAssignments.php)

- **[handle](../app/Console/Commands/SyncTournamentScoringAssignments.php#L15)** — Sincroniza las puntuaciones asignadas con las tablas vigentes de los tipos de torneo.
  Firma: `public function handle(TournamentScoringService $service): int`

### Console/Commands/VerifyClientConfiguration.php

[Abrir archivo completo](../app/Console/Commands/VerifyClientConfiguration.php)

- **[handle](../app/Console/Commands/VerifyClientConfiguration.php#L15)** — Verifica la identidad y los archivos de esta instalación sin modificar datos ni mostrar credenciales.
  Firma: `public function handle(): int`


## Exceptions

### Exceptions/Handler.php

[Abrir archivo completo](../app/Exceptions/Handler.php)

- **[register](../app/Exceptions/Handler.php#L20)** — Registra servicios y dependencias en el contenedor.
  Firma: `public function register(): void`
- **[render](../app/Exceptions/Handler.php#L30)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `render`.
  Firma: `public function render($request, Throwable $e)`

### Exceptions/TournamentRegulationBlockedException.php

[Abrir archivo completo](../app/Exceptions/TournamentRegulationBlockedException.php)

- **[__construct](../app/Exceptions/TournamentRegulationBlockedException.php#L10)** — Constructor de la clase; sus parámetros y operaciones de inicialización se encuentran en el enlace.
  Firma: `public function __construct( public readonly TournamentRegulationEvaluation $evaluation, public readonly bool $isSuperAdmin, string $message = 'El torneo incumple una o más reglas reglamentarias.', )`


## Filament

### Filament/Actions/GlobalActionGroup.php

[Abrir archivo completo](../app/Filament/Actions/GlobalActionGroup.php)

- **[make](../app/Filament/Actions/GlobalActionGroup.php#L10)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `public static function make(array $actions = []): static`

### Filament/Actions/GlobalDeleteAction.php

[Abrir archivo completo](../app/Filament/Actions/GlobalDeleteAction.php)

- **[make](../app/Filament/Actions/GlobalDeleteAction.php#L10)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `public static function make(?string $name = null): static`

### Filament/Actions/GlobalEditAction.php

[Abrir archivo completo](../app/Filament/Actions/GlobalEditAction.php)

- **[make](../app/Filament/Actions/GlobalEditAction.php#L10)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `public static function make(?string $name = null): static`

### Filament/Actions/GlobalViewAction.php

[Abrir archivo completo](../app/Filament/Actions/GlobalViewAction.php)

- **[make](../app/Filament/Actions/GlobalViewAction.php#L10)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `public static function make(?string $name = null): static`

### Filament/Pages/Auth/Login.php

[Abrir archivo completo](../app/Filament/Pages/Auth/Login.php)

- **[form](../app/Filament/Pages/Auth/Login.php#L12)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public function form(Schema $schema): Schema`
- **[getNameFormComponent](../app/Filament/Pages/Auth/Login.php#L30)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `protected function getNameFormComponent(): Component`
- **[getCredentialsFromFormData](../app/Filament/Pages/Auth/Login.php#L39)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected function getCredentialsFromFormData(array $data): array`

### Filament/Pages/Dashboard.php

[Abrir archivo completo](../app/Filament/Pages/Dashboard.php)

- **[getHeading](../app/Filament/Pages/Dashboard.php#L10)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function getHeading(): string|Htmlable`
- **[getPageClasses](../app/Filament/Pages/Dashboard.php#L15)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getPageClasses`.
  Firma: `public function getPageClasses(): array`

### Filament/Pages/FiveQuillasRanking.php

[Abrir archivo completo](../app/Filament/Pages/FiveQuillasRanking.php)

- **[getHeaderWidgets](../app/Filament/Pages/FiveQuillasRanking.php#L25)** — Selecciona los widgets de cabecera.
  Firma: `protected function getHeaderWidgets(): array`
- **[getHeaderWidgetsColumns](../app/Filament/Pages/FiveQuillasRanking.php#L30)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function getHeaderWidgetsColumns(): int|array`

### Filament/Resources/BankTransferOrders/BankTransferOrderResource.php

[Abrir archivo completo](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php)

- **[getEloquentQuery](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L44)** — Define la consulta base del recurso y sus restricciones.
  Firma: `public static function getEloquentQuery(): Builder`
- **[canCreate](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L51)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canCreate(): bool`
- **[canViewAny](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L56)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `check`.
  Firma: `public static function canViewAny(): bool`
- **[canView](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L61)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `id`.
  Firma: `public static function canView($record): bool`
- **[canEdit](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L66)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `id`.
  Firma: `public static function canEdit($record): bool`
- **[canDelete](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L76)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canDelete($record): bool`
- **[form](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L81)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L134)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L221)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`
- **[statusLabels](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L229)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function statusLabels(): array`
- **[shareByWhatsAppAction](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L238)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function shareByWhatsAppAction(): Action`
- **[whatsAppShareUrl](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L254)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `temporarySignedRoute`, `money`.
  Firma: `public static function whatsAppShareUrl(BankTransferOrder $order): string`

Acciones identificadas por nombre interno:

- [download](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L171) · etiqueta «Descargar orden PDF»
- [approve](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L183) · etiqueta «Aprobar»
- [observe](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L189) · etiqueta «Observar»
- [reject](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L197) · etiqueta «Rechazar»
- [deletePayment](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L204) · etiqueta «Eliminar pago»
- [shareWhatsApp](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php#L240) · etiqueta «Compartir por WhatsApp»

### Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php

[Abrir archivo completo](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php)

- **[getHeaderActions](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php#L18)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getFormActions](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php#L45)** — Configura las acciones disponibles al guardar o cancelar el formulario.
  Firma: `protected function getFormActions(): array`
- **[mutateFormDataBeforeSave](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php#L54)** — Prepara los datos del formulario antes de guardar el registro.
  Firma: `protected function mutateFormDataBeforeSave(array $data): array`
- **[getRedirectUrl](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php#L62)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[registrationTournament](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php#L67)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function registrationTournament(): ?Tournament`

Acciones identificadas por nombre interno:

- [addTournamentRegistration](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php#L21) · etiqueta «Agregar otra inscripción»
- [submit](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php#L35) · etiqueta «Enviar a revisión»

### Filament/Resources/BankTransferSettings/BankTransferSettingResource.php

[Abrir archivo completo](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php)

- **[canViewAny](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php#L31)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canViewAny(): bool`
- **[canCreate](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php#L36)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `query`.
  Firma: `public static function canCreate(): bool`
- **[canEdit](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php#L41)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canEdit($record): bool`
- **[canDelete](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php#L46)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canDelete($record): bool`
- **[form](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php#L51)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php#L68)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php#L83)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/BankTransferSettings/Pages/ListBankTransferSettings.php

[Abrir archivo completo](../app/Filament/Resources/BankTransferSettings/Pages/ListBankTransferSettings.php)

- **[getHeaderActions](../app/Filament/Resources/BankTransferSettings/Pages/ListBankTransferSettings.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Categories/CategoryResource.php

[Abrir archivo completo](../app/Filament/Resources/Categories/CategoryResource.php)

- **[form](../app/Filament/Resources/Categories/CategoryResource.php#L35)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Categories/CategoryResource.php#L40)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/Categories/CategoryResource.php#L45)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/Categories/Pages/CreateCategory.php

[Abrir archivo completo](../app/Filament/Resources/Categories/Pages/CreateCategory.php)

- **[getRedirectUrl](../app/Filament/Resources/Categories/Pages/CreateCategory.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/Categories/Pages/CreateCategory.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Categories/Pages/EditCategory.php

[Abrir archivo completo](../app/Filament/Resources/Categories/Pages/EditCategory.php)

- **[getHeaderActions](../app/Filament/Resources/Categories/Pages/EditCategory.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/Categories/Pages/EditCategory.php#L21)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/Categories/Pages/EditCategory.php#L26)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Categories/Pages/ListCategories.php

[Abrir archivo completo](../app/Filament/Resources/Categories/Pages/ListCategories.php)

- **[getHeaderActions](../app/Filament/Resources/Categories/Pages/ListCategories.php#L13)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Categories/Schemas/CategoryForm.php

[Abrir archivo completo](../app/Filament/Resources/Categories/Schemas/CategoryForm.php)

- **[configure](../app/Filament/Resources/Categories/Schemas/CategoryForm.php#L12)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Categories/Tables/CategoriesTable.php

[Abrir archivo completo](../app/Filament/Resources/Categories/Tables/CategoriesTable.php)

- **[configure](../app/Filament/Resources/Categories/Tables/CategoriesTable.php#L16)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/Cities/CityResource.php

[Abrir archivo completo](../app/Filament/Resources/Cities/CityResource.php)

- **[form](../app/Filament/Resources/Cities/CityResource.php#L27)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Cities/CityResource.php#L32)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Cities/CityResource.php#L37)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Cities/CityResource.php#L44)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/Cities/Pages/CreateCity.php

[Abrir archivo completo](../app/Filament/Resources/Cities/Pages/CreateCity.php)

- **[getRedirectUrl](../app/Filament/Resources/Cities/Pages/CreateCity.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/Cities/Pages/CreateCity.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Cities/Pages/EditCity.php

[Abrir archivo completo](../app/Filament/Resources/Cities/Pages/EditCity.php)

- **[getHeaderActions](../app/Filament/Resources/Cities/Pages/EditCity.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[afterSave](../app/Filament/Resources/Cities/Pages/EditCity.php#L21)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Cities/Pages/ListCities.php

[Abrir archivo completo](../app/Filament/Resources/Cities/Pages/ListCities.php)

- **[getHeaderActions](../app/Filament/Resources/Cities/Pages/ListCities.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Cities/Schemas/CityForm.php

[Abrir archivo completo](../app/Filament/Resources/Cities/Schemas/CityForm.php)

- **[configure](../app/Filament/Resources/Cities/Schemas/CityForm.php#L13)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`
- **[schema](../app/Filament/Resources/Cities/Schemas/CityForm.php#L18)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`, `pluck`, `where`.
  Firma: `public static function schema(): array`

### Filament/Resources/Cities/Tables/CitiesTable.php

[Abrir archivo completo](../app/Filament/Resources/Cities/Tables/CitiesTable.php)

- **[configure](../app/Filament/Resources/Cities/Tables/CitiesTable.php#L18)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/Clubs/ClubResource.php

[Abrir archivo completo](../app/Filament/Resources/Clubs/ClubResource.php)

- **[form](../app/Filament/Resources/Clubs/ClubResource.php#L32)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Clubs/ClubResource.php#L37)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Clubs/ClubResource.php#L42)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Clubs/ClubResource.php#L49)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`
- **[getRecordRouteBindingEloquentQuery](../app/Filament/Resources/Clubs/ClubResource.php#L58)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getRecordRouteBindingEloquentQuery`.
  Firma: `public static function getRecordRouteBindingEloquentQuery(): Builder`
- **[exportToPdf](../app/Filament/Resources/Clubs/ClubResource.php#L66)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `loadView`, `logo`, `footer`.
  Firma: `public static function exportToPdf($records, string $title)`
- **[viewAfiliatesAction](../app/Filament/Resources/Clubs/ClubResource.php#L123)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function viewAfiliatesAction(): Action`
- **[locationAction](../app/Filament/Resources/Clubs/ClubResource.php#L143)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function locationAction(): Action`

Acciones identificadas por nombre interno:

- [Afiliados](../app/Filament/Resources/Clubs/ClubResource.php#L126) · etiqueta «Ver Afiliados»
- [ubicacion](../app/Filament/Resources/Clubs/ClubResource.php#L145) · etiqueta «Ubicación»

### Filament/Resources/Clubs/Pages/CreateClub.php

[Abrir archivo completo](../app/Filament/Resources/Clubs/Pages/CreateClub.php)

- **[getRedirectUrl](../app/Filament/Resources/Clubs/Pages/CreateClub.php#L18)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[mutateFormDataBeforeCreate](../app/Filament/Resources/Clubs/Pages/CreateClub.php#L23)** — Prepara los datos del formulario antes de crear el registro.
  Firma: `protected function mutateFormDataBeforeCreate(array $data): array`
- **[afterCreate](../app/Filament/Resources/Clubs/Pages/CreateClub.php#L55)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Clubs/Pages/EditClub.php

[Abrir archivo completo](../app/Filament/Resources/Clubs/Pages/EditClub.php)

- **[getHeaderActions](../app/Filament/Resources/Clubs/Pages/EditClub.php#L16)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getTitle](../app/Filament/Resources/Clubs/Pages/EditClub.php#L25)** — Devuelve el título visible de la página.
  Firma: `public function getTitle(): string`
- **[getRedirectUrl](../app/Filament/Resources/Clubs/Pages/EditClub.php#L30)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/Clubs/Pages/EditClub.php#L35)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Clubs/Pages/ListClubs.php

[Abrir archivo completo](../app/Filament/Resources/Clubs/Pages/ListClubs.php)

- **[getHeaderActions](../app/Filament/Resources/Clubs/Pages/ListClubs.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Clubs/Schemas/ClubForm.php

[Abrir archivo completo](../app/Filament/Resources/Clubs/Schemas/ClubForm.php)

- **[getFormSchema](../app/Filament/Resources/Clubs/Schemas/ClubForm.php#L20)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`, `pluck`, `where`, `find`, `with`, `query`, `withMessages`.
  Firma: `public static function getFormSchema(): array`
- **[configure](../app/Filament/Resources/Clubs/Schemas/ClubForm.php#L239)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Clubs/Tables/ClubsTable.php

[Abrir archivo completo](../app/Filament/Resources/Clubs/Tables/ClubsTable.php)

- **[configure](../app/Filament/Resources/Clubs/Tables/ClubsTable.php#L24)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

Acciones identificadas por nombre interno:

- [descargarPdf](../app/Filament/Resources/Clubs/Tables/ClubsTable.php#L94) · etiqueta «Exportar PDF»

### Filament/Resources/Concerns/ScopesToUserDisciplines.php

[Abrir archivo completo](../app/Filament/Resources/Concerns/ScopesToUserDisciplines.php)

- **[getEloquentQuery](../app/Filament/Resources/Concerns/ScopesToUserDisciplines.php#L10)** — Define la consulta base del recurso y sus restricciones.
  Firma: `public static function getEloquentQuery(): Builder`
- **[applyDisciplineScope](../app/Filament/Resources/Concerns/ScopesToUserDisciplines.php#L27)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected static function applyDisciplineScope(Builder $query, array $disciplineIds): Builder`
- **[applyAssignmentScope](../app/Filament/Resources/Concerns/ScopesToUserDisciplines.php#L32)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected static function applyAssignmentScope(Builder $query, $user): Builder`
- **[disciplineViewPermission](../app/Filament/Resources/Concerns/ScopesToUserDisciplines.php#L37)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getModel`.
  Firma: `protected static function disciplineViewPermission(): string`

### Filament/Resources/Countries/CountryResource.php

[Abrir archivo completo](../app/Filament/Resources/Countries/CountryResource.php)

- **[form](../app/Filament/Resources/Countries/CountryResource.php#L29)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[infolist](../app/Filament/Resources/Countries/CountryResource.php#L34)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `configure`.
  Firma: `public static function infolist(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Countries/CountryResource.php#L39)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Countries/CountryResource.php#L44)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Countries/CountryResource.php#L51)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/Countries/Pages/CreateCountry.php

[Abrir archivo completo](../app/Filament/Resources/Countries/Pages/CreateCountry.php)

- **[getRedirectUrl](../app/Filament/Resources/Countries/Pages/CreateCountry.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/Countries/Pages/CreateCountry.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Countries/Pages/EditCountry.php

[Abrir archivo completo](../app/Filament/Resources/Countries/Pages/EditCountry.php)

- **[getHeaderActions](../app/Filament/Resources/Countries/Pages/EditCountry.php#L15)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/Countries/Pages/EditCountry.php#L23)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/Countries/Pages/EditCountry.php#L28)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Countries/Pages/ListCountries.php

[Abrir archivo completo](../app/Filament/Resources/Countries/Pages/ListCountries.php)

- **[getHeaderActions](../app/Filament/Resources/Countries/Pages/ListCountries.php#L13)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Countries/Pages/ViewCountry.php

[Abrir archivo completo](../app/Filament/Resources/Countries/Pages/ViewCountry.php)

- **[getHeaderActions](../app/Filament/Resources/Countries/Pages/ViewCountry.php#L13)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Countries/Schemas/CountryForm.php

[Abrir archivo completo](../app/Filament/Resources/Countries/Schemas/CountryForm.php)

- **[configure](../app/Filament/Resources/Countries/Schemas/CountryForm.php#L12)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Countries/Schemas/CountryInfolist.php

[Abrir archivo completo](../app/Filament/Resources/Countries/Schemas/CountryInfolist.php)

- **[configure](../app/Filament/Resources/Countries/Schemas/CountryInfolist.php#L11)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Countries/Tables/CountriesTable.php

[Abrir archivo completo](../app/Filament/Resources/Countries/Tables/CountriesTable.php)

- **[configure](../app/Filament/Resources/Countries/Tables/CountriesTable.php#L17)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/DisciplineModalities/DisciplineModalityResource.php

[Abrir archivo completo](../app/Filament/Resources/DisciplineModalities/DisciplineModalityResource.php)

- **[form](../app/Filament/Resources/DisciplineModalities/DisciplineModalityResource.php#L44)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/DisciplineModalities/DisciplineModalityResource.php#L65)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/DisciplineModalities/DisciplineModalityResource.php#L85)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/DisciplineModalities/Pages/CreateDisciplineModality.php

[Abrir archivo completo](../app/Filament/Resources/DisciplineModalities/Pages/CreateDisciplineModality.php)

- **[getRedirectUrl](../app/Filament/Resources/DisciplineModalities/Pages/CreateDisciplineModality.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/DisciplineModalities/Pages/CreateDisciplineModality.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/DisciplineModalities/Pages/EditDisciplineModality.php

[Abrir archivo completo](../app/Filament/Resources/DisciplineModalities/Pages/EditDisciplineModality.php)

- **[getHeaderActions](../app/Filament/Resources/DisciplineModalities/Pages/EditDisciplineModality.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/DisciplineModalities/Pages/EditDisciplineModality.php#L19)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/DisciplineModalities/Pages/EditDisciplineModality.php#L24)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/DisciplineModalities/Pages/ListDisciplineModalities.php

[Abrir archivo completo](../app/Filament/Resources/DisciplineModalities/Pages/ListDisciplineModalities.php)

- **[getHeaderActions](../app/Filament/Resources/DisciplineModalities/Pages/ListDisciplineModalities.php#L13)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Disciplines/DisciplineResource.php

[Abrir archivo completo](../app/Filament/Resources/Disciplines/DisciplineResource.php)

- **[applyDisciplineScope](../app/Filament/Resources/Disciplines/DisciplineResource.php#L25)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected static function applyDisciplineScope(\Illuminate\Database\Eloquent\Builder $query, array $disciplineIds): \Illuminate\Database\Eloquent\Builder`
- **[form](../app/Filament/Resources/Disciplines/DisciplineResource.php#L40)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Disciplines/DisciplineResource.php#L45)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Disciplines/DisciplineResource.php#L50)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Disciplines/DisciplineResource.php#L57)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/Disciplines/Pages/CreateDiscipline.php

[Abrir archivo completo](../app/Filament/Resources/Disciplines/Pages/CreateDiscipline.php)

- **[getRedirectUrl](../app/Filament/Resources/Disciplines/Pages/CreateDiscipline.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/Disciplines/Pages/CreateDiscipline.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Disciplines/Pages/EditDiscipline.php

[Abrir archivo completo](../app/Filament/Resources/Disciplines/Pages/EditDiscipline.php)

- **[getHeaderActions](../app/Filament/Resources/Disciplines/Pages/EditDiscipline.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/Disciplines/Pages/EditDiscipline.php#L21)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/Disciplines/Pages/EditDiscipline.php#L26)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Disciplines/Pages/ListDisciplines.php

[Abrir archivo completo](../app/Filament/Resources/Disciplines/Pages/ListDisciplines.php)

- **[getHeaderActions](../app/Filament/Resources/Disciplines/Pages/ListDisciplines.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Disciplines/Schemas/DisciplineForm.php

[Abrir archivo completo](../app/Filament/Resources/Disciplines/Schemas/DisciplineForm.php)

- **[getFormSchema](../app/Filament/Resources/Disciplines/Schemas/DisciplineForm.php#L14)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `public static function getFormSchema(): array`
- **[configure](../app/Filament/Resources/Disciplines/Schemas/DisciplineForm.php#L60)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Disciplines/Tables/DisciplinesTable.php

[Abrir archivo completo](../app/Filament/Resources/Disciplines/Tables/DisciplinesTable.php)

- **[configure](../app/Filament/Resources/Disciplines/Tables/DisciplinesTable.php#L18)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/Federations/FederationResource.php

[Abrir archivo completo](../app/Filament/Resources/Federations/FederationResource.php)

- **[form](../app/Filament/Resources/Federations/FederationResource.php#L27)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Federations/FederationResource.php#L32)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Federations/FederationResource.php#L37)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Federations/FederationResource.php#L44)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/Federations/Pages/CreateFederation.php

[Abrir archivo completo](../app/Filament/Resources/Federations/Pages/CreateFederation.php)

- **[getRedirectUrl](../app/Filament/Resources/Federations/Pages/CreateFederation.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/Federations/Pages/CreateFederation.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Federations/Pages/EditFederation.php

[Abrir archivo completo](../app/Filament/Resources/Federations/Pages/EditFederation.php)

- **[getHeaderActions](../app/Filament/Resources/Federations/Pages/EditFederation.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/Federations/Pages/EditFederation.php#L21)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/Federations/Pages/EditFederation.php#L26)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Federations/Pages/ListFederations.php

[Abrir archivo completo](../app/Filament/Resources/Federations/Pages/ListFederations.php)

- **[getHeaderActions](../app/Filament/Resources/Federations/Pages/ListFederations.php#L15)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Federations/Schemas/FederationForm.php

[Abrir archivo completo](../app/Filament/Resources/Federations/Schemas/FederationForm.php)

- **[configure](../app/Filament/Resources/Federations/Schemas/FederationForm.php#L13)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Federations/Tables/FederationsTable.php

[Abrir archivo completo](../app/Filament/Resources/Federations/Tables/FederationsTable.php)

- **[configure](../app/Filament/Resources/Federations/Tables/FederationsTable.php#L19)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

Acciones identificadas por nombre interno:

- [verClubes](../app/Filament/Resources/Federations/Tables/FederationsTable.php#L51) · etiqueta «Clubes»

### Filament/Resources/MembershipAccounts/MembershipAccountResource.php

[Abrir archivo completo](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php)

- **[canViewAny](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L30)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canViewAny(): bool`
- **[canCreate](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L35)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canCreate(): bool`
- **[canEdit](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L36)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canEdit($record): bool`
- **[canDelete](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L37)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canDelete($record): bool`
- **[getEloquentQuery](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L39)** — Define la consulta base del recurso y sus restricciones.
  Firma: `public static function getEloquentQuery(): Builder`
- **[table](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L54)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L118)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

Acciones identificadas por nombre interno:

- [history](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L96) · etiqueta «Ver movimientos»
- [print](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L103) · etiqueta «Ver / imprimir cuenta»
- [share](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php#L108) · etiqueta «Compartir cuenta»

### Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php

[Abrir archivo completo](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php)

- **[getHeaderActions](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php#L29)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[entityStatementSchema](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php#L121)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`, `query`.
  Firma: `private function entityStatementSchema(): array`
- **[entityStatementUrl](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php#L154)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `temporarySignedRoute`.
  Firma: `private function entityStatementUrl(array $data): string`
- **[entityAccounts](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php#L163)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `with`, `query`.
  Firma: `private function entityAccounts(array $data)`

Acciones identificadas por nombre interno:

- [recordInstitutionalPayment](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php#L33) · etiqueta «Registrar pago institucional»
- [printEntityStatement](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php#L86) · etiqueta «Imprimir estado consolidado»
- [shareEntityStatement](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php#L96) · etiqueta «Compartir estado consolidado»

### Filament/Resources/MembershipPayments/MembershipPaymentResource.php

[Abrir archivo completo](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php)

- **[canViewAny](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L35)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canViewAny(): bool`
- **[canCreate](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L39)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canCreate(): bool`
- **[canEdit](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L40)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canEdit($record): bool`
- **[canDelete](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L44)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canDelete($record): bool`
- **[getEloquentQuery](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L46)** — Define la consulta base del recurso y sus restricciones.
  Firma: `public static function getEloquentQuery(): Builder`
- **[form](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L59)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L71)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L97)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

Acciones identificadas por nombre interno:

- [print](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L86) · etiqueta «Ver / imprimir»
- [share](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php#L88) · etiqueta «Compartir»

### Filament/Resources/MembershipPayments/Pages/EditMembershipPayment.php

[Abrir archivo completo](../app/Filament/Resources/MembershipPayments/Pages/EditMembershipPayment.php)

- **[getHeaderActions](../app/Filament/Resources/MembershipPayments/Pages/EditMembershipPayment.php#L16)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[afterSave](../app/Filament/Resources/MembershipPayments/Pages/EditMembershipPayment.php#L25)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`
- **[getRedirectUrl](../app/Filament/Resources/MembershipPayments/Pages/EditMembershipPayment.php#L30)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`

Acciones identificadas por nombre interno:

- [print](../app/Filament/Resources/MembershipPayments/Pages/EditMembershipPayment.php#L19) · etiqueta «Ver / imprimir»

### Filament/Resources/Memberships/MembershipResource.php

[Abrir archivo completo](../app/Filament/Resources/Memberships/MembershipResource.php)

- **[disciplineViewPermission](../app/Filament/Resources/Memberships/MembershipResource.php#L38)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected static function disciplineViewPermission(): string`
- **[canViewAny](../app/Filament/Resources/Memberships/MembershipResource.php#L43)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canViewAny(): bool`
- **[canCreate](../app/Filament/Resources/Memberships/MembershipResource.php#L48)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canCreate(): bool`
- **[canEdit](../app/Filament/Resources/Memberships/MembershipResource.php#L53)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canEdit($record): bool`
- **[canDelete](../app/Filament/Resources/Memberships/MembershipResource.php#L58)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`.
  Firma: `public static function canDelete($record): bool`
- **[form](../app/Filament/Resources/Memberships/MembershipResource.php#L63)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Memberships/MembershipResource.php#L68)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/Memberships/MembershipResource.php#L73)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/Memberships/Pages/CreateMembership.php

[Abrir archivo completo](../app/Filament/Resources/Memberships/Pages/CreateMembership.php)

- **[getRedirectUrl](../app/Filament/Resources/Memberships/Pages/CreateMembership.php#L12)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`

### Filament/Resources/Memberships/Pages/EditMembership.php

[Abrir archivo completo](../app/Filament/Resources/Memberships/Pages/EditMembership.php)

- **[getRedirectUrl](../app/Filament/Resources/Memberships/Pages/EditMembership.php#L12)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`

### Filament/Resources/Memberships/Pages/ListMemberships.php

[Abrir archivo completo](../app/Filament/Resources/Memberships/Pages/ListMemberships.php)

- **[getHeaderActions](../app/Filament/Resources/Memberships/Pages/ListMemberships.php#L17)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Memberships/Schemas/MembershipForm.php

[Abrir archivo completo](../app/Filament/Resources/Memberships/Schemas/MembershipForm.php)

- **[configure](../app/Filament/Resources/Memberships/Schemas/MembershipForm.php#L15)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Memberships/Tables/MembershipsTable.php

[Abrir archivo completo](../app/Filament/Resources/Memberships/Tables/MembershipsTable.php)

- **[configure](../app/Filament/Resources/Memberships/Tables/MembershipsTable.php#L12)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/Players/Pages/CategoryChangesReport.php

[Abrir archivo completo](../app/Filament/Resources/Players/Pages/CategoryChangesReport.php)

- **[getHeaderActions](../app/Filament/Resources/Players/Pages/CategoryChangesReport.php#L31)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[table](../app/Filament/Resources/Players/Pages/CategoryChangesReport.php#L70)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`

Acciones identificadas por nombre interno:

- [exportPdf](../app/Filament/Resources/Players/Pages/CategoryChangesReport.php#L34) · etiqueta «Generar PDF»

### Filament/Resources/Players/Pages/CreatePlayer.php

[Abrir archivo completo](../app/Filament/Resources/Players/Pages/CreatePlayer.php)

- **[mutateFormDataBeforeCreate](../app/Filament/Resources/Players/Pages/CreatePlayer.php#L16)** — Prepara los datos del formulario antes de crear el registro.
  Firma: `protected function mutateFormDataBeforeCreate(array $data): array`
- **[getRedirectUrl](../app/Filament/Resources/Players/Pages/CreatePlayer.php#L31)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/Players/Pages/CreatePlayer.php#L36)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Players/Pages/EditPlayer.php

[Abrir archivo completo](../app/Filament/Resources/Players/Pages/EditPlayer.php)

- **[mutateFormDataBeforeSave](../app/Filament/Resources/Players/Pages/EditPlayer.php#L18)** — Prepara los datos del formulario antes de guardar el registro.
  Firma: `protected function mutateFormDataBeforeSave(array $data): array`
- **[getHeaderActions](../app/Filament/Resources/Players/Pages/EditPlayer.php#L33)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[afterSave](../app/Filament/Resources/Players/Pages/EditPlayer.php#L42)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Players/Pages/FilteredPlayerPerformance.php

[Abrir archivo completo](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php)

- **[mount](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L56)** — Inicializa y valida el contexto al abrir la página Livewire.
  Firma: `public function mount(string $report): void`
- **[disciplineOptions](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L72)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `public function disciplineOptions(): array`
- **[typeOptions](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L77)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `public function typeOptions(): array`
- **[federationOptions](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L87)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `public function federationOptions(): array`
- **[categoryOptions](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L92)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `public function categoryOptions(): array`
- **[updatedDisciplineId](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L99)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function updatedDisciplineId(): void`
- **[reportData](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L108)** — La pantalla y el PDF comparten exactamente la misma selección.
  Firma: `public function reportData(): array`
- **[getHeaderActions](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L200)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[startPdfExport](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L211)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `uuid`, `id`, `dispatch`.
  Firma: `public function startPdfExport(): void`
- **[finishPdfExport](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L226)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `public function finishPdfExport(string $token, bool $successful): void`

Acciones identificadas por nombre interno:

- [exportPdf](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php#L203) · etiqueta «Exportar PDF»

### Filament/Resources/Players/Pages/ListPlayers.php

[Abrir archivo completo](../app/Filament/Resources/Players/Pages/ListPlayers.php)

- **[getHeaderWidgets](../app/Filament/Resources/Players/Pages/ListPlayers.php#L16)** — Selecciona los widgets de cabecera.
  Firma: `protected function getHeaderWidgets(): array`
- **[getHeaderActions](../app/Filament/Resources/Players/Pages/ListPlayers.php#L24)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Players/Pages/PlayerPerformance.php

[Abrir archivo completo](../app/Filament/Resources/Players/Pages/PlayerPerformance.php)

- **[mount](../app/Filament/Resources/Players/Pages/PlayerPerformance.php#L33)** — Inicializa y valida el contexto al abrir la página Livewire.
  Firma: `public function mount(int|string $record): void`
- **[getTitle](../app/Filament/Resources/Players/Pages/PlayerPerformance.php#L38)** — Devuelve el título visible de la página.
  Firma: `public function getTitle(): string`
- **[getHeaderActions](../app/Filament/Resources/Players/Pages/PlayerPerformance.php#L43)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[table](../app/Filament/Resources/Players/Pages/PlayerPerformance.php#L74)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`
- **[totals](../app/Filament/Resources/Players/Pages/PlayerPerformance.php#L140)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getFilteredTableQuery`.
  Firma: `public function totals(): array`

Acciones identificadas por nombre interno:

- [exportPdf](../app/Filament/Resources/Players/Pages/PlayerPerformance.php#L46) · etiqueta «Exportar PDF»

### Filament/Resources/Players/PlayerResource.php

[Abrir archivo completo](../app/Filament/Resources/Players/PlayerResource.php)

- **[applyAssignmentScope](../app/Filament/Resources/Players/PlayerResource.php#L55)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineViewPermission`.
  Firma: `protected static function applyAssignmentScope(Builder $query, $user): Builder`
- **[form](../app/Filament/Resources/Players/PlayerResource.php#L87)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Players/PlayerResource.php#L92)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Players/PlayerResource.php#L97)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Players/PlayerResource.php#L104)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`
- **[getRecordRouteBindingEloquentQuery](../app/Filament/Resources/Players/PlayerResource.php#L116)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getEloquentQuery`.
  Firma: `public static function getRecordRouteBindingEloquentQuery(): Builder`
- **[exportToPdf](../app/Filament/Resources/Players/PlayerResource.php#L125)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `where`, `loadView`, `logo`, `footer`.
  Firma: `public static function exportToPdf($records, string $title)`
- **[changeCategoryAction](../app/Filament/Resources/Players/PlayerResource.php#L195)** — Acción para programar o aplicar un cambio manual de categoría permanente de afiliación.
  Firma: `public static function changeCategoryAction(): Action`
- **[viewCategoryHistoryAction](../app/Filament/Resources/Players/PlayerResource.php#L299)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function viewCategoryHistoryAction(): Action`
- **[payMembershipAction](../app/Filament/Resources/Players/PlayerResource.php#L319)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function payMembershipAction(): Action`
- **[processPayMembership](../app/Filament/Resources/Players/PlayerResource.php#L334)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`, `make`, `find`.
  Firma: `public static function processPayMembership($records): void`
- **[exportarPdf](../app/Filament/Resources/Players/PlayerResource.php#L376)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`, `exportToPdf`.
  Firma: `public static function exportarPdf(): Action`
- **[importPlayers](../app/Filament/Resources/Players/PlayerResource.php#L393)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `make`, `import`.
  Firma: `public static function importPlayers(): Action`
- **[bulkCreateAction](../app/Filament/Resources/Players/PlayerResource.php#L418)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function bulkCreateAction(): Action`
- **[configureAnnualMembershipAction](../app/Filament/Resources/Players/PlayerResource.php#L676)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function configureAnnualMembershipAction(): Action`
- **[renewClubMembershipsAction](../app/Filament/Resources/Players/PlayerResource.php#L709)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function renewClubMembershipsAction(): Action`

Acciones identificadas por nombre interno:

- [changeCategory](../app/Filament/Resources/Players/PlayerResource.php#L197) · etiqueta «Cambiar categoría»
- [viewCategoryHistory](../app/Filament/Resources/Players/PlayerResource.php#L301) · etiqueta «Historial de categorías»
- [payMembership](../app/Filament/Resources/Players/PlayerResource.php#L323) · etiqueta «Afiliación»
- [descargarPdf](../app/Filament/Resources/Players/PlayerResource.php#L378) · etiqueta «Exportar PDF»
- [importPlayers](../app/Filament/Resources/Players/PlayerResource.php#L398) · etiqueta «Importar jugadores»
- [bulkCreatePlayers](../app/Filament/Resources/Players/PlayerResource.php#L420) · etiqueta «Alta masiva»
- [configureAnnualMembership](../app/Filament/Resources/Players/PlayerResource.php#L678) · etiqueta «Configurar membresía anual»
- [renewClubMemberships](../app/Filament/Resources/Players/PlayerResource.php#L711) · etiqueta «Renovación masiva»

### Filament/Resources/Players/RelationManagers/CategoryHistoriesRelationManager.php

[Abrir archivo completo](../app/Filament/Resources/Players/RelationManagers/CategoryHistoriesRelationManager.php)

- **[form](../app/Filament/Resources/Players/RelationManagers/CategoryHistoriesRelationManager.php#L25)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Players/RelationManagers/CategoryHistoriesRelationManager.php#L72)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`

### Filament/Resources/Players/Schemas/PlayerForm.php

[Abrir archivo completo](../app/Filament/Resources/Players/Schemas/PlayerForm.php)

- **[configure](../app/Filament/Resources/Players/Schemas/PlayerForm.php#L22)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Players/Tables/PlayersTable.php

[Abrir archivo completo](../app/Filament/Resources/Players/Tables/PlayersTable.php)

- **[configure](../app/Filament/Resources/Players/Tables/PlayersTable.php#L30)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

Acciones identificadas por nombre interno:

- [verDesempeno](../app/Filament/Resources/Players/Tables/PlayersTable.php#L187) · etiqueta «Desempeño en torneos»
- [payMembership](../app/Filament/Resources/Players/Tables/PlayersTable.php#L198) · etiqueta «Afiliación»
- [desactivar](../app/Filament/Resources/Players/Tables/PlayersTable.php#L206) · etiqueta «Desactivar seleccionados»
- [activar](../app/Filament/Resources/Players/Tables/PlayersTable.php#L222) · etiqueta «Activar seleccionados»
- [performanceFiltered](../app/Filament/Resources/Players/Tables/PlayersTable.php#L239) · etiqueta «Desempeño de jugadores»
- [categoryChangesReport](../app/Filament/Resources/Players/Tables/PlayersTable.php#L262) · etiqueta «Cambios de categoría»

### Filament/Resources/Players/Traits/AppliesPlayerFilters.php

[Abrir archivo completo](../app/Filament/Resources/Players/Traits/AppliesPlayerFilters.php)

- **[applyPlayersFilters](../app/Filament/Resources/Players/Traits/AppliesPlayerFilters.php#L13)** — Aplica los filtros de la tabla del recurso Players a cualquier query usada en widgets (stats, charts, etc.)
  Firma: `public function applyPlayersFilters(Builder $query, array $filters, ?string $search = null): Builder`

### Filament/Resources/Players/Widgets/PlayerChart.php

[Abrir archivo completo](../app/Filament/Resources/Players/Widgets/PlayerChart.php)

- **[getType](../app/Filament/Resources/Players/Widgets/PlayerChart.php#L19)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected function getType(): string`
- **[getData](../app/Filament/Resources/Players/Widgets/PlayerChart.php#L24)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `applyPlayersFilters`, `query`, `whereIn`.
  Firma: `protected function getData(): array`

### Filament/Resources/States/Pages/CreateState.php

[Abrir archivo completo](../app/Filament/Resources/States/Pages/CreateState.php)

- **[getRedirectUrl](../app/Filament/Resources/States/Pages/CreateState.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/States/Pages/CreateState.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/States/Pages/EditState.php

[Abrir archivo completo](../app/Filament/Resources/States/Pages/EditState.php)

- **[getHeaderActions](../app/Filament/Resources/States/Pages/EditState.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/States/Pages/EditState.php#L21)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/States/Pages/EditState.php#L26)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/States/Pages/ListStates.php

[Abrir archivo completo](../app/Filament/Resources/States/Pages/ListStates.php)

- **[getHeaderActions](../app/Filament/Resources/States/Pages/ListStates.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/States/Schemas/StateForm.php

[Abrir archivo completo](../app/Filament/Resources/States/Schemas/StateForm.php)

- **[configure](../app/Filament/Resources/States/Schemas/StateForm.php#L12)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/States/StateResource.php

[Abrir archivo completo](../app/Filament/Resources/States/StateResource.php)

- **[form](../app/Filament/Resources/States/StateResource.php#L27)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/States/StateResource.php#L32)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/States/StateResource.php#L37)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/States/StateResource.php#L44)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/States/Tables/StatesTable.php

[Abrir archivo completo](../app/Filament/Resources/States/Tables/StatesTable.php)

- **[configure](../app/Filament/Resources/States/Tables/StatesTable.php#L17)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/ThreeCushionRankings/Pages/ListThreeCushionRankings.php

[Abrir archivo completo](../app/Filament/Resources/ThreeCushionRankings/Pages/ListThreeCushionRankings.php)

- **[getTitle](../app/Filament/Resources/ThreeCushionRankings/Pages/ListThreeCushionRankings.php#L12)** — Devuelve el título visible de la página.
  Firma: `public function getTitle(): string`

### Filament/Resources/ThreeCushionRankings/Tables/ThreeCushionRankingsTable.php

[Abrir archivo completo](../app/Filament/Resources/ThreeCushionRankings/Tables/ThreeCushionRankingsTable.php)

- **[configure](../app/Filament/Resources/ThreeCushionRankings/Tables/ThreeCushionRankingsTable.php#L18)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

Acciones identificadas por nombre interno:

- [exportPdf](../app/Filament/Resources/ThreeCushionRankings/Tables/ThreeCushionRankingsTable.php#L58) · etiqueta «Exportar PDF»

### Filament/Resources/ThreeCushionRankings/ThreeCushionRankingResource.php

[Abrir archivo completo](../app/Filament/Resources/ThreeCushionRankings/ThreeCushionRankingResource.php)

- **[table](../app/Filament/Resources/ThreeCushionRankings/ThreeCushionRankingResource.php#L26)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[canCreate](../app/Filament/Resources/ThreeCushionRankings/ThreeCushionRankingResource.php#L31)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function canCreate(): bool`
- **[getPages](../app/Filament/Resources/ThreeCushionRankings/ThreeCushionRankingResource.php#L36)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/TournamentInstances/Pages/CreateTournamentInstance.php

[Abrir archivo completo](../app/Filament/Resources/TournamentInstances/Pages/CreateTournamentInstance.php)

- **[afterCreate](../app/Filament/Resources/TournamentInstances/Pages/CreateTournamentInstance.php#L13)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/TournamentInstances/Pages/EditTournamentInstance.php

[Abrir archivo completo](../app/Filament/Resources/TournamentInstances/Pages/EditTournamentInstance.php)

- **[getHeaderActions](../app/Filament/Resources/TournamentInstances/Pages/EditTournamentInstance.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[afterSave](../app/Filament/Resources/TournamentInstances/Pages/EditTournamentInstance.php#L21)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/TournamentInstances/Pages/ListTournamentInstances.php

[Abrir archivo completo](../app/Filament/Resources/TournamentInstances/Pages/ListTournamentInstances.php)

- **[getHeaderActions](../app/Filament/Resources/TournamentInstances/Pages/ListTournamentInstances.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/TournamentInstances/Schemas/TournamentInstanceForm.php

[Abrir archivo completo](../app/Filament/Resources/TournamentInstances/Schemas/TournamentInstanceForm.php)

- **[configure](../app/Filament/Resources/TournamentInstances/Schemas/TournamentInstanceForm.php#L11)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/TournamentInstances/Tables/TournamentInstancesTable.php

[Abrir archivo completo](../app/Filament/Resources/TournamentInstances/Tables/TournamentInstancesTable.php)

- **[configure](../app/Filament/Resources/TournamentInstances/Tables/TournamentInstancesTable.php#L15)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/TournamentInstances/TournamentInstanceResource.php

[Abrir archivo completo](../app/Filament/Resources/TournamentInstances/TournamentInstanceResource.php)

- **[form](../app/Filament/Resources/TournamentInstances/TournamentInstanceResource.php#L29)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/TournamentInstances/TournamentInstanceResource.php#L34)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/TournamentInstances/TournamentInstanceResource.php#L39)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/TournamentInstances/TournamentInstanceResource.php#L46)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/TournamentRegistrations/Pages/CreateTournamentRegistration.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegistrations/Pages/CreateTournamentRegistration.php)

- **[getRedirectUrl](../app/Filament/Resources/TournamentRegistrations/Pages/CreateTournamentRegistration.php#L17)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `paymentOrderUrl`, `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/TournamentRegistrations/Pages/CreateTournamentRegistration.php#L23)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`
- **[getSavedNotificationRedirectUrl](../app/Filament/Resources/TournamentRegistrations/Pages/CreateTournamentRegistration.php#L41)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function getSavedNotificationRedirectUrl(): ?string`

### Filament/Resources/TournamentRegistrations/Pages/EditTournamentRegistration.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegistrations/Pages/EditTournamentRegistration.php)

- **[getHeaderActions](../app/Filament/Resources/TournamentRegistrations/Pages/EditTournamentRegistration.php#L23)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/TournamentRegistrations/Pages/EditTournamentRegistration.php#L51)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[getSavedNotificationRedirectUrl](../app/Filament/Resources/TournamentRegistrations/Pages/EditTournamentRegistration.php#L56)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function getSavedNotificationRedirectUrl(): ?string`
- **[afterSave](../app/Filament/Resources/TournamentRegistrations/Pages/EditTournamentRegistration.php#L61)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php)

- **[configure](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php#L20)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema, ?Tournament $tournament): Schema`
- **[resolveTournament](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php#L69)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `isNested`, `find`.
  Firma: `private static function resolveTournament(Get $get, $livewire, ?Tournament $tournament): ?Tournament`
- **[requiredPlayers](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php#L81)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `with`.
  Firma: `private static function requiredPlayers(mixed $configurationId): int`
- **[modalityCount](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php#L86)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function modalityCount(?Tournament $tournament): int`
- **[singleModalityId](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php#L91)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `modalityCount`.
  Firma: `private static function singleModalityId(?Tournament $tournament): ?int`
- **[eligiblePlayers](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php#L100)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `find`, `whereIn`, `query`.
  Firma: `private static function eligiblePlayers(mixed $configurationId): array`

### Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php)

- **[configure](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L50)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table, $tournament): Table`
- **[bulkSlotAssignmentAction](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L480)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `private static function bulkSlotAssignmentAction($tournament): Action`
- **[bulkScoringAction](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L630)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `private static function bulkScoringAction($tournament): Action`
- **[resolveTournament](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L863)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function resolveTournament($tournament, $livewire)`
- **[scoringRuleOptions](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L877)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function scoringRuleOptions($tournament, bool $officialInstances): array`
- **[usesOfficialInstances](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L895)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function usesOfficialInstances($tournament): bool`
- **[slotOptions](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L902)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function slotOptions($tournament): array`
- **[assignableSlotOptions](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L924)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `isUnassignedSlotName`.
  Firma: `private static function assignableSlotOptions($tournament): array`
- **[unassignedRegistrationOptions](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L950)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `isUnassignedSlotName`.
  Firma: `private static function unassignedRegistrationOptions($tournament, ?int $targetSlotId): array`
- **[isUnassignedSlotName](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L1010)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function isUnassignedSlotName(?string $name): bool`
- **[registrationOptions](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L1015)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function registrationOptions($tournament, ?int $slotId = null): array`
- **[individualScoringRows](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L1054)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private static function individualScoringRows($tournament, ?int $slotId): array`

Acciones identificadas por nombre interno:

- [exportarInscripcionesPdf](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L314) · etiqueta «Exportar PDF»
- [resultadoTresBandas](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L397) · etiqueta «Datos 3 Bandas»
- [cambiarEstado](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L433) · etiqueta «Cambiar Estado»
- [pdf](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L466) · etiqueta «PDF»
- [asignarHorariosMasivos](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L482) · etiqueta «Asignar horarios»
- [asignarPuntajesMasivos](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php#L632) · etiqueta «Asignar puntajes»

### Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php)

- **[paymentOrderUrl](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L39)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getUrl`.
  Firma: `public static function paymentOrderUrl(TournamentRegistration $registration): ?string`
- **[continuePaymentAction](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L57)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function continuePaymentAction(): Action`
- **[shareByWhatsAppAction](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L74)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function shareByWhatsAppAction(): Action`
- **[registrationWhatsAppUrl](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L92)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `whatsAppRecipient`, `temporarySignedRoute`.
  Firma: `public static function registrationWhatsAppUrl(TournamentRegistration $registration): string`
- **[whatsAppRecipient](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L132)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function whatsAppRecipient(TournamentRegistration $registration): ?string`
- **[applyDisciplineScope](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L163)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected static function applyDisciplineScope(Builder $query, array $disciplineIds): Builder`
- **[applyAssignmentScope](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L171)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineViewPermission`.
  Firma: `protected static function applyAssignmentScope(Builder $query, $user): Builder`
- **[disciplineViewPermission](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L193)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected static function disciplineViewPermission(): string`
- **[form](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L208)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L213)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L218)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L225)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`
- **[getRecordRouteBindingEloquentQuery](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L234)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getEloquentQuery`.
  Firma: `public static function getRecordRouteBindingEloquentQuery(): Builder`
- **[exportRegistrationsToPdf](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L242)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `absolute`, `logo`, `footer`, `where`, `limit`, `loadView`.
  Firma: `public static function exportRegistrationsToPdf($tournament, $slotId = null, $status = null)`
- **[shouldRegisterNavigation](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L342)** — Determina si se muestra el recurso en el menú.
  Firma: `public static function shouldRegisterNavigation(): bool`
- **[isNested](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L347)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function isNested($livewire): bool`
- **[AsignInstanceAction](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L353)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function AsignInstanceAction(): Action`
- **[afterSave](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L572)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `public static function afterSave($record): void`

Acciones identificadas por nombre interno:

- [continuePayment](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L59) · etiqueta «Continuar pago»
- [shareRegistrationWhatsApp](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L76) · revisar etiqueta y callback en la definición
- [asignarInstancia](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php#L355) · etiqueta «Asignar posición»

### Filament/Resources/TournamentRegistrations/Traits/DetectsNestedContext.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegistrations/Traits/DetectsNestedContext.php)

- **[isNested](../app/Filament/Resources/TournamentRegistrations/Traits/DetectsNestedContext.php#L7)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function isNested($livewire): bool`

### Filament/Resources/TournamentRegulationAudits/Tables/TournamentRegulationAuditsTable.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationAudits/Tables/TournamentRegulationAuditsTable.php)

- **[configure](../app/Filament/Resources/TournamentRegulationAudits/Tables/TournamentRegulationAuditsTable.php#L14)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

Acciones identificadas por nombre interno:

- [downloadPdf](../app/Filament/Resources/TournamentRegulationAudits/Tables/TournamentRegulationAuditsTable.php#L44) · etiqueta «Descargar informe»

### Filament/Resources/TournamentRegulationAudits/TournamentRegulationAuditResource.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationAudits/TournamentRegulationAuditResource.php)

- **[table](../app/Filament/Resources/TournamentRegulationAudits/TournamentRegulationAuditResource.php#L18)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/TournamentRegulationAudits/TournamentRegulationAuditResource.php#L19)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/TournamentRegulationSettings/Pages/CreateTournamentRegulationSetting.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationSettings/Pages/CreateTournamentRegulationSetting.php)

- **[afterCreate](../app/Filament/Resources/TournamentRegulationSettings/Pages/CreateTournamentRegulationSetting.php#L13)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/TournamentRegulationSettings/Pages/EditTournamentRegulationSetting.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationSettings/Pages/EditTournamentRegulationSetting.php)

- **[getHeaderActions](../app/Filament/Resources/TournamentRegulationSettings/Pages/EditTournamentRegulationSetting.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[afterSave](../app/Filament/Resources/TournamentRegulationSettings/Pages/EditTournamentRegulationSetting.php#L27)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/TournamentRegulationSettings/Pages/ListTournamentRegulationSettings.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationSettings/Pages/ListTournamentRegulationSettings.php)

- **[getHeaderActions](../app/Filament/Resources/TournamentRegulationSettings/Pages/ListTournamentRegulationSettings.php#L9)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/TournamentRegulationSettings/Schemas/TournamentRegulationSettingForm.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationSettings/Schemas/TournamentRegulationSettingForm.php)

- **[configure](../app/Filament/Resources/TournamentRegulationSettings/Schemas/TournamentRegulationSettingForm.php#L15)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/TournamentRegulationSettings/Tables/TournamentRegulationSettingsTable.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationSettings/Tables/TournamentRegulationSettingsTable.php)

- **[configure](../app/Filament/Resources/TournamentRegulationSettings/Tables/TournamentRegulationSettingsTable.php#L14)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/TournamentRegulationSettings/TournamentRegulationSettingResource.php

[Abrir archivo completo](../app/Filament/Resources/TournamentRegulationSettings/TournamentRegulationSettingResource.php)

- **[form](../app/Filament/Resources/TournamentRegulationSettings/TournamentRegulationSettingResource.php#L24)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/TournamentRegulationSettings/TournamentRegulationSettingResource.php#L25)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getPages](../app/Filament/Resources/TournamentRegulationSettings/TournamentRegulationSettingResource.php#L26)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Resources/Tournaments/Pages/Concerns/InteractsWithTournamentRegulationModal.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Pages/Concerns/InteractsWithTournamentRegulationModal.php)

- **[regulatoryConflictAction](../app/Filament/Resources/Tournaments/Pages/Concerns/InteractsWithTournamentRegulationModal.php#L11)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `protected function regulatoryConflictAction(): Action`
- **[showRegulatoryConflictModal](../app/Filament/Resources/Tournaments/Pages/Concerns/InteractsWithTournamentRegulationModal.php#L34)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `mountAction`, `halt`.
  Firma: `protected function showRegulatoryConflictModal(TournamentRegulationBlockedException $exception): never`
- **[regulatoryModalContent](../app/Filament/Resources/Tournaments/Pages/Concerns/InteractsWithTournamentRegulationModal.php#L43)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function regulatoryModalContent(array $conflicts, bool $isSuperAdmin): HtmlString`

Acciones identificadas por nombre interno:

- [regulatoryConflict](../app/Filament/Resources/Tournaments/Pages/Concerns/InteractsWithTournamentRegulationModal.php#L13) · etiqueta «Conflicto reglamentario»

### Filament/Resources/Tournaments/Pages/CreateTournament.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php)

- **[mutateFormDataBeforeCreate](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php#L25)** — Prepara los datos del formulario antes de crear el registro.
  Firma: `protected function mutateFormDataBeforeCreate(array $data): array`
- **[enforceClubScope](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php#L40)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `withMessages`.
  Firma: `private function enforceClubScope(array &$data): void`
- **[selectedCategoryIds](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php#L58)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function selectedCategoryIds(): array`
- **[getHeaderActions](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php#L68)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getFormWidth](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php#L73)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function getFormWidth(): string|int|null`
- **[getRedirectUrl](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php#L78)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php#L83)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Tournaments/Pages/EditTournament.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Pages/EditTournament.php)

- **[mutateFormDataBeforeSave](../app/Filament/Resources/Tournaments/Pages/EditTournament.php#L30)** — Prepara los datos del formulario antes de guardar el registro.
  Firma: `protected function mutateFormDataBeforeSave(array $data): array`
- **[enforceClubScope](../app/Filament/Resources/Tournaments/Pages/EditTournament.php#L50)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `withMessages`.
  Firma: `private function enforceClubScope(array &$data): void`
- **[selectedCategoryIds](../app/Filament/Resources/Tournaments/Pages/EditTournament.php#L68)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function selectedCategoryIds(): array`
- **[getHeaderActions](../app/Filament/Resources/Tournaments/Pages/EditTournament.php#L78)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/Tournaments/Pages/EditTournament.php#L88)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/Tournaments/Pages/EditTournament.php#L93)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Tournaments/Pages/ListTournaments.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Pages/ListTournaments.php)

- **[mount](../app/Filament/Resources/Tournaments/Pages/ListTournaments.php#L15)** — Inicializa y valida el contexto al abrir la página Livewire.
  Firma: `public function mount(): void`
- **[getHeaderActions](../app/Filament/Resources/Tournaments/Pages/ListTournaments.php#L22)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php)

- **[mount](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L52)** — Carga el torneo solicitado, inicializa su competencia y verifica autorización.
  Firma: `public function mount(int|string $record): void`
- **[getTitle](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L81)** — Compone el título visible de la página de organización.
  Firma: `public function getTitle(): string`
- **[getHeaderActions](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L89)** — Define las acciones generales disponibles sobre el torneo.
  Firma: `protected function getHeaderActions(): array`
- **[competition](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L584)** — Obtiene la competencia asociada al registro actual.
  Firma: `public function competition(): ?FiveQuillasCompetition`
- **[nextBracketSize](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L595)** — Calcula la potencia de dos inmediata para una cantidad de participantes.
  Firma: `private function nextBracketSize(int $players): int`
- **[entryOptionsForSlot](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L608)** — Devuelve las entradas seleccionables de un horario excluyendo las ya usadas.
  Firma: `public function entryOptionsForSlot(int $slotId, array $excludedEntryIds = []): array`
- **[zoneEntryOptions](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L657)** — Construye las opciones de jugadores para todos los campos de zonas.
  Firma: `public function zoneEntryOptions(): array`
- **[selectedZoneEntryIds](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L699)** — Reúne las entradas elegidas en campos hermanos del mismo formulario.
  Firma: `private function selectedZoneEntryIds(Select $component, int $currentEntryId = 0): array`
- **[availableZoneEntryOptions](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L718)** — Devuelve las inscripciones disponibles para una posición de zona. Se evalúa cada vez que se abre el selector para reflejar inmediatamente las altas, bajas y cambios realizados en cualquier fila del formulario.
  Firma: `private function availableZoneEntryOptions(Get $get, Select $component): array`
- **[zoneRowsForSlot](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L736)** — Reconstruye las filas editables y bloqueadas de zonas para un horario.
  Firma: `public function zoneRowsForSlot(int $slotId): array`
- **[createZonesAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L802)** — Configura el modal que crea o corrige zonas antes de que comiencen.
  Firma: `public function createZonesAction(): Action`
- **[zoneDrawNumberAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L892)** — Registra desde la zona el número sorteado por un clasificado.
  Firma: `public function zoneDrawNumberAction(): Action`
- **[loadRegisteredMastersAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L922)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public function loadRegisteredMastersAction(): Action`
- **[preparePhaseAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L952)** — Configura la revisión de participantes y preparación de una fase.
  Firma: `public function preparePhaseAction(): Action`
- **[configureSlotCrossingsAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1089)** — Configura manualmente cruces entre clasificados de zonas por horario.
  Firma: `public function configureSlotCrossingsAction(): Action`
- **[slotZonePositionOptions](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1215)** — Lista clasificados de un puesto y horario ordenados por número de zona.
  Firma: `public function slotZonePositionOptions(int $phaseId, int $slotId, int $place, array $excludedGroups = []): array`
- **[availableSlotCrossingOptions](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1248)** — Filtra opciones usadas por otros cruces sin ocultar la selección actual.
  Firma: `private function availableSlotCrossingOptions(array $options, Select $component, string $field): array`
- **[schedulePhaseAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1266)** — Configura la asignación de horarios y mesas de una fase.
  Firma: `public function schedulePhaseAction(): Action`
- **[scheduleWaitingMatchAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1321)** — Informa el horario propio de una posición de la grilla, aun cuando sus jugadores todavía no estén definidos.
  Firma: `public function scheduleWaitingMatchAction(): Action`
- **[editFinalScoreAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1368)** — Permite corregir la regla o puntuación propuesta de una entrada final.
  Firma: `public function editFinalScoreAction(): Action`
- **[saveFinalScoringAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1395)** — Confirma la acreditación final de puntos sin duplicar registros.
  Firma: `public function saveFinalScoringAction(): Action`
- **[proposedFinalPoints](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1424)** — Obtiene la puntuación calculada para mostrarla antes de confirmar.
  Firma: `public function proposedFinalPoints(FiveQuillasEntry $entry): ?float`
- **[scoringRuleOptions](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1436)** — Lista reglas de puntuación elegibles para el tipo de torneo.
  Firma: `private function scoringRuleOptions(): array`
- **[finalInstanceLabel](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1456)** — Devuelve la instancia final persistida de una entrada.
  Firma: `public function finalInstanceLabel(FiveQuillasEntry $entry): string`
- **[finalPerformance](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1468)** — Obtiene los indicadores que justifican la posición final.
  Firma: `public function finalPerformance(FiveQuillasEntry $entry): array`
- **[recordResultAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1482)** — Configura el formulario de sets y proclamación de ganador.
  Firma: `public function recordResultAction(): Action`
- **[promptQualifiedDraw](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1644)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `competition`, `replaceMountedAction`.
  Firma: `private function promptQualifiedDraw(FiveQuillasMatch $match): void`
- **[resetResultAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1661)** — Permite revertir un resultado y sus propagaciones derivadas.
  Firma: `public function resetResultAction(): Action`
- **[correctMatchParticipantsAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1704)** — Corrige nombres de la primera ronda conservando números de posición.
  Firma: `public function correctMatchParticipantsAction(): Action`
- **[matchPlayersLabel](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1765)** — Construye una etiqueta breve con los jugadores de un partido.
  Firma: `private function matchPlayersLabel(int $matchId): string`
- **[competitionMatch](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1777)** — Busca un partido y verifica que pertenezca a la competencia abierta.
  Firma: `private function competitionMatch(int $matchId): ?FiveQuillasMatch`
- **[callMatchAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1792)** — Marca un partido como llamado para su disputa.
  Firma: `public function callMatchAction(): Action`
- **[cancelMatchCallAction](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1840)** — Cancela el llamado de un partido que todavía no comenzó y libera su mesa.
  Firma: `public function cancelMatchCallAction(): Action`
- **[getViewData](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1865)** — Prepara fases, horarios, tablas, clasificados y estados para la vista.
  Firma: `protected function getViewData(): array`

Acciones identificadas por nombre interno:

- [initialize](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L92) · revisar etiqueta y callback en la definición
- [publish](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L102) · etiqueta «Configurar vista pública»
- [broadcast](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L184) · etiqueta «Configurar vista pública»
- [configureFormat](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L351) · etiqueta «Configurar formato»
- [applyCompetitionTemplate](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L545) · etiqueta «Elegir formato inicial»
- [createZones](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L804) · etiqueta «Configurar zonas»
- [zoneDrawNumber](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L894) · etiqueta «Cargar sorteo»
- [loadRegisteredMasters](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L924) · etiqueta «Cargar Masters del horario»
- [preparePhase](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L954) · etiqueta «Preparar / actualizar grilla»
- [configureSlotCrossings](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1091) · etiqueta «Configurar cruces»
- [schedulePhase](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1268) · etiqueta «Asignar horarios»
- [scheduleWaitingMatch](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1323) · etiqueta «Informar horario»
- [editFinalScore](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1370) · etiqueta «Revisar puntuación»
- [saveFinalScoring](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1397) · etiqueta «Guardar puntuación»
- [recordResult](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1484) · etiqueta «Cargar / editar resultado»
- [resetResult](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1663) · etiqueta «Reiniciar partido»
- [correctMatchParticipants](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1706) · etiqueta «Corregir jugadores»
- [callMatch](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1794) · etiqueta «Llamar»
- [cancelMatchCall](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php#L1842) · etiqueta «Cancelar llamado»

### Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php)

- **[getTitle](../app/Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php#L24)** — Devuelve el título visible de la página.
  Firma: `public function getTitle(): string`
- **[form](../app/Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php#L33)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php#L40)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`
- **[getTabs](../app/Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php#L45)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getOwnerRecord`, `make`.
  Firma: `public function getTabs(): array`
- **[isNested](../app/Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php#L75)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function isNested($livewire): bool`

### Filament/Resources/Tournaments/Pages/ViewRegistrations.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Pages/ViewRegistrations.php)

- **[table](../app/Filament/Resources/Tournaments/Pages/ViewRegistrations.php#L26)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`

### Filament/Resources/Tournaments/RelationManagers/RegistrationsRelationManager.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/RelationManagers/RegistrationsRelationManager.php)

- **[form](../app/Filament/Resources/Tournaments/RelationManagers/RegistrationsRelationManager.php#L19)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Tournaments/RelationManagers/RegistrationsRelationManager.php#L26)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`
- **[getTabs](../app/Filament/Resources/Tournaments/RelationManagers/RegistrationsRelationManager.php#L34)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`.
  Firma: `public function getTabs(): array`

### Filament/Resources/Tournaments/Schemas/TournamentForm.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Schemas/TournamentForm.php)

- **[configure](../app/Filament/Resources/Tournaments/Schemas/TournamentForm.php#L35)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`
- **[getResource](../app/Filament/Resources/Tournaments/Schemas/TournamentForm.php#L622)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function getResource(): string`

### Filament/Resources/Tournaments/Tables/TournamentsTable.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/Tables/TournamentsTable.php)

- **[configure](../app/Filament/Resources/Tournaments/Tables/TournamentsTable.php#L29)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/Tournaments/TournamentResource.php

[Abrir archivo completo](../app/Filament/Resources/Tournaments/TournamentResource.php)

- **[applyAssignmentScope](../app/Filament/Resources/Tournaments/TournamentResource.php#L31)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineViewPermission`.
  Firma: `protected static function applyAssignmentScope(Builder $query, $user): Builder`
- **[form](../app/Filament/Resources/Tournaments/TournamentResource.php#L67)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Tournaments/TournamentResource.php#L72)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Tournaments/TournamentResource.php#L77)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Tournaments/TournamentResource.php#L84)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`
- **[getRecordSubNavigation](../app/Filament/Resources/Tournaments/TournamentResource.php#L97)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getCurrentPanel`.
  Firma: `public static function getRecordSubNavigation(Page $page): array`
- **[inscriptionsAction](../app/Filament/Resources/Tournaments/TournamentResource.php#L120)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function inscriptionsAction(): Action`
- **[resumenAction](../app/Filament/Resources/Tournaments/TournamentResource.php#L135)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function resumenAction(): Action`

Acciones identificadas por nombre interno:

- [manageRegistrations](../app/Filament/Resources/Tournaments/TournamentResource.php#L123) · etiqueta «Inscripciones»
- [resumenTorneo](../app/Filament/Resources/Tournaments/TournamentResource.php#L137) · etiqueta «Resumen»

### Filament/Resources/TournamentTypes/Pages/CreateTournamentType.php

[Abrir archivo completo](../app/Filament/Resources/TournamentTypes/Pages/CreateTournamentType.php)

- **[getRedirectUrl](../app/Filament/Resources/TournamentTypes/Pages/CreateTournamentType.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterCreate](../app/Filament/Resources/TournamentTypes/Pages/CreateTournamentType.php#L18)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/TournamentTypes/Pages/EditTournamentType.php

[Abrir archivo completo](../app/Filament/Resources/TournamentTypes/Pages/EditTournamentType.php)

- **[getHeaderActions](../app/Filament/Resources/TournamentTypes/Pages/EditTournamentType.php#L16)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[getRedirectUrl](../app/Filament/Resources/TournamentTypes/Pages/EditTournamentType.php#L25)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getResource`, `getUrl`.
  Firma: `protected function getRedirectUrl(): string`
- **[afterSave](../app/Filament/Resources/TournamentTypes/Pages/EditTournamentType.php#L30)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/TournamentTypes/Pages/ListTournamentTypes.php

[Abrir archivo completo](../app/Filament/Resources/TournamentTypes/Pages/ListTournamentTypes.php)

- **[getHeaderActions](../app/Filament/Resources/TournamentTypes/Pages/ListTournamentTypes.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/TournamentTypes/Schemas/TournamentTypeForm.php

[Abrir archivo completo](../app/Filament/Resources/TournamentTypes/Schemas/TournamentTypeForm.php)

- **[configure](../app/Filament/Resources/TournamentTypes/Schemas/TournamentTypeForm.php#L20)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`
- **[isFiveQuillas](../app/Filament/Resources/TournamentTypes/Schemas/TournamentTypeForm.php#L278)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `private static function isFiveQuillas(mixed $disciplineId): bool`

Acciones identificadas por nombre interno:

- [importCurrentRankingPositions](../app/Filament/Resources/TournamentTypes/Schemas/TournamentTypeForm.php#L125) · etiqueta «Importar posiciones actuales»

### Filament/Resources/TournamentTypes/Tables/TournamentTypesTable.php

[Abrir archivo completo](../app/Filament/Resources/TournamentTypes/Tables/TournamentTypesTable.php)

- **[configure](../app/Filament/Resources/TournamentTypes/Tables/TournamentTypesTable.php#L22)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/TournamentTypes/TournamentTypeResource.php

[Abrir archivo completo](../app/Filament/Resources/TournamentTypes/TournamentTypeResource.php)

- **[form](../app/Filament/Resources/TournamentTypes/TournamentTypeResource.php#L37)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/TournamentTypes/TournamentTypeResource.php#L42)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/TournamentTypes/TournamentTypeResource.php#L47)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/TournamentTypes/TournamentTypeResource.php#L54)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`
- **[getRecordRouteBindingEloquentQuery](../app/Filament/Resources/TournamentTypes/TournamentTypeResource.php#L63)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getEloquentQuery`.
  Firma: `public static function getRecordRouteBindingEloquentQuery(): Builder`

### Filament/Resources/Users/Pages/CreateUser.php

[Abrir archivo completo](../app/Filament/Resources/Users/Pages/CreateUser.php)

- **[afterCreate](../app/Filament/Resources/Users/Pages/CreateUser.php#L13)** — Ejecuta las operaciones posteriores a la creación del registro.
  Firma: `protected function afterCreate(): void`

### Filament/Resources/Users/Pages/EditUser.php

[Abrir archivo completo](../app/Filament/Resources/Users/Pages/EditUser.php)

- **[getHeaderActions](../app/Filament/Resources/Users/Pages/EditUser.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`
- **[afterSave](../app/Filament/Resources/Users/Pages/EditUser.php#L21)** — Ejecuta las operaciones posteriores al guardado del registro.
  Firma: `protected function afterSave(): void`

### Filament/Resources/Users/Pages/ListUsers.php

[Abrir archivo completo](../app/Filament/Resources/Users/Pages/ListUsers.php)

- **[getHeaderActions](../app/Filament/Resources/Users/Pages/ListUsers.php#L14)** — Construye las acciones de la cabecera. Revisar sus formularios y callbacks en este método.
  Firma: `protected function getHeaderActions(): array`

### Filament/Resources/Users/Schemas/UserForm.php

[Abrir archivo completo](../app/Filament/Resources/Users/Schemas/UserForm.php)

- **[configure](../app/Filament/Resources/Users/Schemas/UserForm.php#L16)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Schema $schema): Schema`

### Filament/Resources/Users/Tables/UsersTable.php

[Abrir archivo completo](../app/Filament/Resources/Users/Tables/UsersTable.php)

- **[configure](../app/Filament/Resources/Users/Tables/UsersTable.php#L17)** — Construye la configuración de formulario o listado de esta clase.
  Firma: `public static function configure(Table $table): Table`

### Filament/Resources/Users/UserResource.php

[Abrir archivo completo](../app/Filament/Resources/Users/UserResource.php)

- **[form](../app/Filament/Resources/Users/UserResource.php#L27)** — Define el formulario administrativo y los componentes que lo integran.
  Firma: `public static function form(Schema $schema): Schema`
- **[table](../app/Filament/Resources/Users/UserResource.php#L32)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public static function table(Table $table): Table`
- **[getRelations](../app/Filament/Resources/Users/UserResource.php#L37)** — Registra los administradores de relaciones del recurso.
  Firma: `public static function getRelations(): array`
- **[getPages](../app/Filament/Resources/Users/UserResource.php#L44)** — Registra las páginas y rutas relativas de este recurso.
  Firma: `public static function getPages(): array`

### Filament/Widgets/DashboardHeaderWidget.php

[Abrir archivo completo](../app/Filament/Widgets/DashboardHeaderWidget.php)

- **[getViewData](../app/Filament/Widgets/DashboardHeaderWidget.php#L17)** — Prepara los datos entregados a la vista. No confundir con el HTML que los presenta.
  Firma: `protected function getViewData(): array`

### Filament/Widgets/PlayersByClubChart.php

[Abrir archivo completo](../app/Filament/Widgets/PlayersByClubChart.php)

- **[filtersSchema](../app/Filament/Widgets/PlayersByClubChart.php#L24)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`, `pluck`.
  Firma: `public function filtersSchema(Schema $schema): Schema`
- **[updatedFederationFilter](../app/Filament/Widgets/PlayersByClubChart.php#L34)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `refresh`.
  Firma: `public function updatedFederationFilter(): void`
- **[getData](../app/Filament/Widgets/PlayersByClubChart.php#L39)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`, `generateColors`.
  Firma: `protected function getData(): array`
- **[getType](../app/Filament/Widgets/PlayersByClubChart.php#L79)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected function getType(): string`
- **[generateColors](../app/Filament/Widgets/PlayersByClubChart.php#L84)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `protected function generateColors(int $count): array`

### Filament/Widgets/RankingGeneralWidget.php

[Abrir archivo completo](../app/Filament/Widgets/RankingGeneralWidget.php)

- **[table](../app/Filament/Widgets/RankingGeneralWidget.php#L38)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`

Acciones identificadas por nombre interno:

- [campeonatoArgentino](../app/Filament/Widgets/RankingGeneralWidget.php#L70) · etiqueta «Campeonato Argentino»
- [descargarPdf](../app/Filament/Widgets/RankingGeneralWidget.php#L154) · etiqueta «Exportar PDF»

### Filament/Widgets/RankingsOverviewWidget.php

[Abrir archivo completo](../app/Filament/Widgets/RankingsOverviewWidget.php)

- **[getViewData](../app/Filament/Widgets/RankingsOverviewWidget.php#L22)** — Prepara los datos entregados a la vista. No confundir con el HTML que los presenta.
  Firma: `protected function getViewData(): array`

### Filament/Widgets/SportsSummaryWidget.php

[Abrir archivo completo](../app/Filament/Widgets/SportsSummaryWidget.php)

- **[getStats](../app/Filament/Widgets/SportsSummaryWidget.php#L25)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `make`, `query`.
  Firma: `protected function getStats(): array`

### Filament/Widgets/ThreeCushionRankingWidget.php

[Abrir archivo completo](../app/Filament/Widgets/ThreeCushionRankingWidget.php)

- **[table](../app/Filament/Widgets/ThreeCushionRankingWidget.php#L22)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`

### Filament/Widgets/UpcomingByDisciplineWidget.php

[Abrir archivo completo](../app/Filament/Widgets/UpcomingByDisciplineWidget.php)

- **[getViewData](../app/Filament/Widgets/UpcomingByDisciplineWidget.php#L20)** — Prepara los datos entregados a la vista. No confundir con el HTML que los presenta.
  Firma: `protected function getViewData(): array`

### Filament/Widgets/UpcomingTournamentsWidget.php

[Abrir archivo completo](../app/Filament/Widgets/UpcomingTournamentsWidget.php)

- **[table](../app/Filament/Widgets/UpcomingTournamentsWidget.php#L23)** — Define columnas filtros y acciones del listado administrativo.
  Firma: `public function table(Table $table): Table`


## Helpers

### Helpers/Cliente.php

[Abrir archivo completo](../app/Helpers/Cliente.php)

- **[money](../app/Helpers/Cliente.php#L7)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function money(float|int|string|null $amount): string`

### Helpers/FabPath.php

[Abrir archivo completo](../app/Helpers/FabPath.php)

- **[root](../app/Helpers/FabPath.php#L7)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public static function root(): string`
- **[absolute](../app/Helpers/FabPath.php#L12)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `root`.
  Firma: `public static function absolute(?string $relative): string`
- **[logo](../app/Helpers/FabPath.php#L21)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `absolute`.
  Firma: `public static function logo(): string`
- **[footer](../app/Helpers/FabPath.php#L26)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `absolute`.
  Firma: `public static function footer(): string`
- **[pagos](../app/Helpers/FabPath.php#L31)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `absolute`.
  Firma: `public static function pagos(string $file = ''): string`
- **[inscripciones](../app/Helpers/FabPath.php#L37)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `absolute`.
  Firma: `public static function inscripciones(string $file = ''): string`

### Helpers/PdfHelper.php

[Abrir archivo completo](../app/Helpers/PdfHelper.php)

- **[pdfToPng](../app/Helpers/PdfHelper.php#L9)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `attach`.
  Firma: `public static function pdfToPng(string $pdfPath, string $pngPath): bool`


## Http

### Http/Controllers/BankTransferOrderPdfController.php

[Abrir archivo completo](../app/Http/Controllers/BankTransferOrderPdfController.php)

- **[__invoke](../app/Http/Controllers/BankTransferOrderPdfController.php#L17)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(BankTransferOrder $order): Response`

### Http/Controllers/FiveQuillasCompetitionBroadcastController.php

[Abrir archivo completo](../app/Http/Controllers/FiveQuillasCompetitionBroadcastController.php)

- **[__invoke](../app/Http/Controllers/FiveQuillasCompetitionBroadcastController.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `public function __invoke(string $slug): View`

### Http/Controllers/FiveQuillasCompetitionPageController.php

[Abrir archivo completo](../app/Http/Controllers/FiveQuillasCompetitionPageController.php)

- **[__invoke](../app/Http/Controllers/FiveQuillasCompetitionPageController.php#L18)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(string $slug): View|Response`

### Http/Controllers/FiveQuillasCompetitionPublicController.php

[Abrir archivo completo](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php)

- **[__invoke](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php#L18)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(string $slug): JsonResponse`
- **[dateLabel](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php#L197)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function dateLabel($start, $end): string`
- **[bracketSlotPlaceholder](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php#L208)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function bracketSlotPlaceholder($phase, $match, int $side): string`
- **[dateTimeLabel](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php#L223)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function dateTimeLabel($dateTime): ?string`
- **[finalInstance](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php#L228)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function finalInstance(FiveQuillasCompetition $competition, $entry): string`
- **[entryPerformance](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php#L233)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function entryPerformance(FiveQuillasCompetition $competition, int $entryId): array`

### Http/Controllers/FiveQuillasCompetitionSectionPdfController.php

[Abrir archivo completo](../app/Http/Controllers/FiveQuillasCompetitionSectionPdfController.php)

- **[__invoke](../app/Http/Controllers/FiveQuillasCompetitionSectionPdfController.php#L20)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(Request $request, string $slug, string $section): Response`
- **[finalInstance](../app/Http/Controllers/FiveQuillasCompetitionSectionPdfController.php#L155)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function finalInstance(FiveQuillasCompetition $competition, $entry): string`
- **[entryPerformance](../app/Http/Controllers/FiveQuillasCompetitionSectionPdfController.php#L160)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `private function entryPerformance(FiveQuillasCompetition $competition, int $entryId): array`

### Http/Controllers/FiveQuillasRankingController.php

[Abrir archivo completo](../app/Http/Controllers/FiveQuillasRankingController.php)

- **[__invoke](../app/Http/Controllers/FiveQuillasRankingController.php#L19)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(Request $request): JsonResponse`

### Http/Controllers/FiveQuillasRankingPdfController.php

[Abrir archivo completo](../app/Http/Controllers/FiveQuillasRankingPdfController.php)

- **[__invoke](../app/Http/Controllers/FiveQuillasRankingPdfController.php#L21)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(Request $request): Response`

### Http/Controllers/MembershipAccountPdfController.php

[Abrir archivo completo](../app/Http/Controllers/MembershipAccountPdfController.php)

- **[__invoke](../app/Http/Controllers/MembershipAccountPdfController.php#L18)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(PlayerMembership $account): Response`

### Http/Controllers/MembershipEntityStatementPdfController.php

[Abrir archivo completo](../app/Http/Controllers/MembershipEntityStatementPdfController.php)

- **[__invoke](../app/Http/Controllers/MembershipEntityStatementPdfController.php#L23)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(Request $request): Response`

### Http/Controllers/MembershipPaymentPdfController.php

[Abrir archivo completo](../app/Http/Controllers/MembershipPaymentPdfController.php)

- **[__invoke](../app/Http/Controllers/MembershipPaymentPdfController.php#L18)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(Payment $payment): Response`

### Http/Controllers/PlayerPerformancePdfDownloadController.php

[Abrir archivo completo](../app/Http/Controllers/PlayerPerformancePdfDownloadController.php)

- **[__invoke](../app/Http/Controllers/PlayerPerformancePdfDownloadController.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `id`, `disk`.
  Firma: `public function __invoke(Request $request, string $token): BinaryFileResponse`

### Http/Controllers/ThreeCushionRankingController.php

[Abrir archivo completo](../app/Http/Controllers/ThreeCushionRankingController.php)

- **[__invoke](../app/Http/Controllers/ThreeCushionRankingController.php#L18)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(Request $request, ThreeCushionRankingService $service): JsonResponse`

### Http/Controllers/TournamentRegistrationPdfController.php

[Abrir archivo completo](../app/Http/Controllers/TournamentRegistrationPdfController.php)

- **[__invoke](../app/Http/Controllers/TournamentRegistrationPdfController.php#L17)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(TournamentRegistration $registration): Response`

### Http/Controllers/UpcomingTournamentsController.php

[Abrir archivo completo](../app/Http/Controllers/UpcomingTournamentsController.php)

- **[__invoke](../app/Http/Controllers/UpcomingTournamentsController.php#L21)** — Atiende la solicitud después de resolver autorización y datos publicados.
  Firma: `public function __invoke(Request $request): JsonResponse`

### Http/Middleware/AssignGuestUser.php

[Abrir archivo completo](../app/Http/Middleware/AssignGuestUser.php)

- **[handle](../app/Http/Middleware/AssignGuestUser.php#L13)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `guest`, `setUser`.
  Firma: `public function handle(Request $request, Closure $next): Response`

### Http/Middleware/DisableShieldAuthForGuestPanel.php

[Abrir archivo completo](../app/Http/Middleware/DisableShieldAuthForGuestPanel.php)

- **[handle](../app/Http/Middleware/DisableShieldAuthForGuestPanel.php#L10)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `getCurrentPanel`.
  Firma: `public function handle($request, Closure $next)`


## Imports

### Imports/PlayersImport.php

[Abrir archivo completo](../app/Imports/PlayersImport.php)

- **[model](../app/Imports/PlayersImport.php#L20)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `info`, `where`, `warning`.
  Firma: `public function model(array $row)`
- **[rules](../app/Imports/PlayersImport.php#L58)** — Define reglas de validación.
  Firma: `public function rules(): array`


## Jobs

### Jobs/GeneratePlayerPerformancePdf.php

[Abrir archivo completo](../app/Jobs/GeneratePlayerPerformancePdf.php)

- **[__construct](../app/Jobs/GeneratePlayerPerformancePdf.php#L13)** — Constructor de la clase; sus parámetros y operaciones de inicialización se encuentran en el enlace.
  Firma: `public function __construct(public string $token, public array $filters)`
- **[handle](../app/Jobs/GeneratePlayerPerformancePdf.php#L15)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `loadView`, `logo`, `footer`, `disk`.
  Firma: `public function handle(): void`


## Mail

### Mail/MembershipRenewalConfirmation.php

[Abrir archivo completo](../app/Mail/MembershipRenewalConfirmation.php)

- **[__construct](../app/Mail/MembershipRenewalConfirmation.php#L23)** — Constructor de la clase; sus parámetros y operaciones de inicialización se encuentran en el enlace.
  Firma: `public function __construct( public Membership $membership, public Collection $renewals, public User $generatedBy, )`
- **[envelope](../app/Mail/MembershipRenewalConfirmation.php#L36)** — Define asunto y datos del sobre del correo.
  Firma: `public function envelope(): Envelope`
- **[content](../app/Mail/MembershipRenewalConfirmation.php#L43)** — Define la plantilla y datos del correo.
  Firma: `public function content(): Content`
- **[attachments](../app/Mail/MembershipRenewalConfirmation.php#L51)** — Construye los adjuntos del correo.
  Firma: `public function attachments(): array`
- **[viewData](../app/Mail/MembershipRenewalConfirmation.php#L61)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `logo`, `footer`.
  Firma: `private function viewData(): array`

### Mail/TournamentRegistrationNotification.php

[Abrir archivo completo](../app/Mail/TournamentRegistrationNotification.php)

- **[__construct](../app/Mail/TournamentRegistrationNotification.php#L21)** — Create a new message instance.
  Firma: `public function __construct( public TournamentRegistration $record, public string $action = 'Nueva Inscripcion' )`
- **[envelope](../app/Mail/TournamentRegistrationNotification.php#L31)** — Get the message envelope.
  Firma: `public function envelope(): Envelope`
- **[content](../app/Mail/TournamentRegistrationNotification.php#L41)** — Get the message content definition.
  Firma: `public function content(): Content`
- **[attachments](../app/Mail/TournamentRegistrationNotification.php#L53)** — Get the attachments for the message.
  Firma: `public function attachments(): array`


## Models

### Models/BankTransferOrder.php

[Abrir archivo completo](../app/Models/BankTransferOrder.php)

- **[casts](../app/Models/BankTransferOrder.php#L23)** — Define conversiones de atributos del modelo.
  Firma: `protected function casts(): array`
- **[items](../app/Models/BankTransferOrder.php#L32)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function items(): HasMany`
- **[user](../app/Models/BankTransferOrder.php#L37)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function user(): BelongsTo`
- **[club](../app/Models/BankTransferOrder.php#L42)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function club(): BelongsTo`
- **[discipline](../app/Models/BankTransferOrder.php#L47)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline(): BelongsTo`
- **[reviewer](../app/Models/BankTransferOrder.php#L52)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function reviewer(): BelongsTo`

### Models/BankTransferOrderItem.php

[Abrir archivo completo](../app/Models/BankTransferOrderItem.php)

- **[casts](../app/Models/BankTransferOrderItem.php#L13)** — Define conversiones de atributos del modelo.
  Firma: `protected function casts(): array`
- **[order](../app/Models/BankTransferOrderItem.php#L18)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function order(): BelongsTo`
- **[payable](../app/Models/BankTransferOrderItem.php#L23)** — Define una relación Eloquent `morphTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function payable(): MorphTo`

### Models/BankTransferSetting.php

[Abrir archivo completo](../app/Models/BankTransferSetting.php)

- **[casts](../app/Models/BankTransferSetting.php#L14)** — Define conversiones de atributos del modelo.
  Firma: `protected function casts(): array`
- **[active](../app/Models/BankTransferSetting.php#L19)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `public static function active(): ?self`

### Models/Category.php

[Abrir archivo completo](../app/Models/Category.php)

- **[booted](../app/Models/Category.php#L20)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `deleting`, `query`, `withMessages`.
  Firma: `protected static function booted(): void`
- **[players](../app/Models/Category.php#L39)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function players()`
- **[tournaments](../app/Models/Category.php#L44)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournaments()`
- **[discipline](../app/Models/Category.php#L49)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline()`

### Models/City.php

[Abrir archivo completo](../app/Models/City.php)

- **[clubs](../app/Models/City.php#L24)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function clubs()`
- **[state](../app/Models/City.php#L29)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function state(): BelongsTo`

### Models/Club.php

[Abrir archivo completo](../app/Models/Club.php)

- **[booted](../app/Models/Club.php#L43)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `addGlobalScope`.
  Firma: `public static function booted()`
- **[city](../app/Models/Club.php#L50)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function city()`
- **[players](../app/Models/Club.php#L55)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function players()`
- **[payments](../app/Models/Club.php#L61)** — Define una relación Eloquent `morphMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function payments()`
- **[tournaments](../app/Models/Club.php#L66)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournaments()`
- **[hasBankTransferData](../app/Models/Club.php#L71)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function hasBankTransferData(): bool`
- **[getLocationAttribute](../app/Models/Club.php#L105)** — Returns the 'lat' and 'lng' attributes as the computed 'location' attribute, as a standard Google Maps style Point array with 'lat' and 'lng' attributes. Used by the Filament Google Maps package. Requires the 'location' attribute be included in this model's $fillable array.
  Firma: `public function getLocationAttribute(): array`
- **[setLocationAttribute](../app/Models/Club.php#L124)** — Takes a Google style Point array of 'lat' and 'lng' values and assigns them to the 'lat' and 'lng' attributes on this model. Used by the Filament Google Maps package. Requires the 'location' attribute be included in this model's $fillable array.
  Firma: `public function setLocationAttribute(?array $location): void`
- **[getLatLngAttributes](../app/Models/Club.php#L141)** — Get the lat and lng attribute/field names used on this table Used by the Filament Google Maps package.
  Firma: `public static function getLatLngAttributes(): array`
- **[getComputedLocation](../app/Models/Club.php#L156)** — Get the name of the computed location attribute Used by the Filament Google Maps package.
  Firma: `public static function getComputedLocation(): string`

### Models/Discipline.php

[Abrir archivo completo](../app/Models/Discipline.php)

- **[booted](../app/Models/Discipline.php#L31)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `withMessages`.
  Firma: `protected static function booted(): void`
- **[players](../app/Models/Discipline.php#L46)** — Define una relación Eloquent `belongsToMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function players(): BelongsToMany`
- **[tournamentTypes](../app/Models/Discipline.php#L53)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentTypes(): HasMany`
- **[tournaments](../app/Models/Discipline.php#L58)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournaments(): HasMany`
- **[tournamentRegulationSetting](../app/Models/Discipline.php#L63)** — Define una relación Eloquent `hasOne`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentRegulationSetting()`
- **[memberships](../app/Models/Discipline.php#L68)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function memberships(): HasMany`
- **[categories](../app/Models/Discipline.php#L73)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function categories(): HasMany`
- **[modalities](../app/Models/Discipline.php#L78)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function modalities(): HasMany`
- **[primaryPlayers](../app/Models/Discipline.php#L83)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function primaryPlayers(): HasMany`
- **[directFederation](../app/Models/Discipline.php#L88)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function directFederation()`
- **[federationForClub](../app/Models/Discipline.php#L93)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function federationForClub(?Club $club): ?Federation`
- **[allowsIndependentAffiliates](../app/Models/Discipline.php#L102)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function allowsIndependentAffiliates(): bool`

### Models/DisciplineModality.php

[Abrir archivo completo](../app/Models/DisciplineModality.php)

- **[discipline](../app/Models/DisciplineModality.php#L14)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline()`
- **[tournamentModalities](../app/Models/DisciplineModality.php#L19)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentModalities()`
- **[booted](../app/Models/DisciplineModality.php#L24)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `query`, `withMessages`, `deleting`.
  Firma: `protected static function booted(): void`

### Models/DisciplineUserRole.php

[Abrir archivo completo](../app/Models/DisciplineUserRole.php)

- **[user](../app/Models/DisciplineUserRole.php#L13)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function user()`
- **[discipline](../app/Models/DisciplineUserRole.php#L18)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline()`
- **[role](../app/Models/DisciplineUserRole.php#L23)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function role()`
- **[club](../app/Models/DisciplineUserRole.php#L28)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function club()`

### Models/Federation.php

[Abrir archivo completo](../app/Models/Federation.php)

- **[tournaments](../app/Models/Federation.php#L25)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournaments()`
- **[states](../app/Models/Federation.php#L31)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function states(): HasMany`
- **[clubs](../app/Models/Federation.php#L36)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function clubs()`
- **[payments](../app/Models/Federation.php#L41)** — Define una relación Eloquent `morphMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function payments()`

### Models/FiveQuillasCompetition.php

[Abrir archivo completo](../app/Models/FiveQuillasCompetition.php)

- **[tournament](../app/Models/FiveQuillasCompetition.php#L15)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament()`
- **[phases](../app/Models/FiveQuillasCompetition.php#L16)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function phases()`
- **[entries](../app/Models/FiveQuillasCompetition.php#L17)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function entries()`

### Models/FiveQuillasEntry.php

[Abrir archivo completo](../app/Models/FiveQuillasEntry.php)

- **[competition](../app/Models/FiveQuillasEntry.php#L14)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function competition()`
- **[registration](../app/Models/FiveQuillasEntry.php#L15)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registration()`
- **[registrationParticipant](../app/Models/FiveQuillasEntry.php#L16)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registrationParticipant()`
- **[player](../app/Models/FiveQuillasEntry.php#L17)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`
- **[sourceSlot](../app/Models/FiveQuillasEntry.php#L18)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function sourceSlot()`
- **[entryPhase](../app/Models/FiveQuillasEntry.php#L19)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function entryPhase()`

### Models/FiveQuillasGroup.php

[Abrir archivo completo](../app/Models/FiveQuillasGroup.php)

- **[phase](../app/Models/FiveQuillasGroup.php#L15)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function phase()`
- **[slot](../app/Models/FiveQuillasGroup.php#L16)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function slot()`
- **[groupEntries](../app/Models/FiveQuillasGroup.php#L17)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function groupEntries()`
- **[matches](../app/Models/FiveQuillasGroup.php#L18)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function matches()`

### Models/FiveQuillasGroupEntry.php

[Abrir archivo completo](../app/Models/FiveQuillasGroupEntry.php)

- **[group](../app/Models/FiveQuillasGroupEntry.php#L15)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function group()`
- **[entry](../app/Models/FiveQuillasGroupEntry.php#L16)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function entry()`

### Models/FiveQuillasMatch.php

[Abrir archivo completo](../app/Models/FiveQuillasMatch.php)

- **[phase](../app/Models/FiveQuillasMatch.php#L15)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function phase()`
- **[group](../app/Models/FiveQuillasMatch.php#L16)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function group()`
- **[playerOne](../app/Models/FiveQuillasMatch.php#L17)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function playerOne()`
- **[playerTwo](../app/Models/FiveQuillasMatch.php#L18)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function playerTwo()`
- **[winner](../app/Models/FiveQuillasMatch.php#L19)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function winner()`
- **[nextMatch](../app/Models/FiveQuillasMatch.php#L20)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function nextMatch()`
- **[loserNextMatch](../app/Models/FiveQuillasMatch.php#L21)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function loserNextMatch()`

### Models/FiveQuillasPhase.php

[Abrir archivo completo](../app/Models/FiveQuillasPhase.php)

- **[competition](../app/Models/FiveQuillasPhase.php#L15)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function competition()`
- **[groups](../app/Models/FiveQuillasPhase.php#L16)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function groups()`
- **[matches](../app/Models/FiveQuillasPhase.php#L17)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function matches()`

### Models/GeneralRanking.php

[Abrir archivo completo](../app/Models/GeneralRanking.php)

- **[player](../app/Models/GeneralRanking.php#L31)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`

### Models/Membership.php

[Abrir archivo completo](../app/Models/Membership.php)

- **[booted](../app/Models/Membership.php#L31)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `query`, `withMessages`.
  Firma: `protected static function booted(): void`
- **[discipline](../app/Models/Membership.php#L48)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline()`
- **[playerMemberships](../app/Models/Membership.php#L53)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function playerMemberships()`

### Models/Payment.php

[Abrir archivo completo](../app/Models/Payment.php)

- **[membership](../app/Models/Payment.php#L37)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function membership()`
- **[payer](../app/Models/Payment.php#L42)** — Define una relación Eloquent `morphTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function payer()`
- **[recorder](../app/Models/Payment.php#L47)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function recorder()`
- **[approve](../app/Models/Payment.php#L58)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `save`.
  Firma: `public function approve(): void`
- **[fail](../app/Models/Payment.php#L70)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `save`.
  Firma: `public function fail(): void`

### Models/Player.php

[Abrir archivo completo](../app/Models/Player.php)

- **[booted](../app/Models/Player.php#L38)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `query`, `withMessages`.
  Firma: `protected static function booted(): void`
- **[club](../app/Models/Player.php#L58)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function club()`
- **[category](../app/Models/Player.php#L63)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function category()`
- **[discipline](../app/Models/Player.php#L68)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline()`
- **[registrations](../app/Models/Player.php#L73)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registrations()`
- **[partnerRegistrations](../app/Models/Player.php#L78)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function partnerRegistrations()`
- **[tournamentRegistrationParticipations](../app/Models/Player.php#L83)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentRegistrationParticipations()`
- **[disciplines](../app/Models/Player.php#L88)** — Define una relación Eloquent `belongsToMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function disciplines()`
- **[affiliationLabel](../app/Models/Player.php#L95)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `loadMissing`.
  Firma: `public function affiliationLabel(): string`
- **[memberships](../app/Models/Player.php#L113)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function memberships()`
- **[payments](../app/Models/Player.php#L118)** — Define una relación Eloquent `morphMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function payments()`
- **[getFullNameAttribute](../app/Models/Player.php#L123)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function getFullNameAttribute(): string`
- **[rankingHistories](../app/Models/Player.php#L128)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function rankingHistories()`
- **[categoryHistories](../app/Models/Player.php#L133)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function categoryHistories()`
- **[categoryPromotions](../app/Models/Player.php#L138)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function categoryPromotions()`

### Models/PlayerCategoryHistory.php

[Abrir archivo completo](../app/Models/PlayerCategoryHistory.php)

- **[player](../app/Models/PlayerCategoryHistory.php#L29)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`
- **[category](../app/Models/PlayerCategoryHistory.php#L34)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function category()`
- **[previousCategory](../app/Models/PlayerCategoryHistory.php#L39)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function previousCategory()`
- **[tournament](../app/Models/PlayerCategoryHistory.php#L44)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament()`
- **[ranking](../app/Models/PlayerCategoryHistory.php#L49)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function ranking()`

### Models/PlayerCategoryPromotion.php

[Abrir archivo completo](../app/Models/PlayerCategoryPromotion.php)

- **[player](../app/Models/PlayerCategoryPromotion.php#L27)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`
- **[previousCategory](../app/Models/PlayerCategoryPromotion.php#L32)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function previousCategory()`
- **[newCategory](../app/Models/PlayerCategoryPromotion.php#L37)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function newCategory()`

### Models/PlayerMembership.php

[Abrir archivo completo](../app/Models/PlayerMembership.php)

- **[player](../app/Models/PlayerMembership.php#L40)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`
- **[membership](../app/Models/PlayerMembership.php#L45)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function membership()`
- **[club](../app/Models/PlayerMembership.php#L50)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function club()`
- **[payments](../app/Models/PlayerMembership.php#L55)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function payments()`
- **[bankTransferOrderItems](../app/Models/PlayerMembership.php#L60)** — Define una relación Eloquent `morphMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function bankTransferOrderItems()`
- **[getBalanceAttribute](../app/Models/PlayerMembership.php#L65)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function getBalanceAttribute(): float`
- **[refreshStatus](../app/Models/PlayerMembership.php#L76)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `save`.
  Firma: `public function refreshStatus(): void`

### Models/RankingHistory.php

[Abrir archivo completo](../app/Models/RankingHistory.php)

- **[player](../app/Models/RankingHistory.php#L32)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`

### Models/RankingSpecialPosition.php

[Abrir archivo completo](../app/Models/RankingSpecialPosition.php)

- **[champion](../app/Models/RankingSpecialPosition.php#L16)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function champion(): BelongsTo`
- **[runnerUp](../app/Models/RankingSpecialPosition.php#L21)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function runnerUp(): BelongsTo`

### Models/State.php

[Abrir archivo completo](../app/Models/State.php)

- **[federation](../app/Models/State.php#L26)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function federation(): BelongsTo`
- **[cities](../app/Models/State.php#L30)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function cities(): HasMany`
- **[country](../app/Models/State.php#L34)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function country(): BelongsTo`

### Models/ThreeCushionRanking.php

[Abrir archivo completo](../app/Models/ThreeCushionRanking.php)

- **[player](../app/Models/ThreeCushionRanking.php#L22)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`
- **[category](../app/Models/ThreeCushionRanking.php#L27)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function category()`
- **[stageDetails](../app/Models/ThreeCushionRanking.php#L32)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`.
  Firma: `public function stageDetails()`

### Models/ThreeCushionStageResult.php

[Abrir archivo completo](../app/Models/ThreeCushionStageResult.php)

- **[booted](../app/Models/ThreeCushionStageResult.php#L29)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `query`, `isThreeCushion`, `withMessages`, `saved`, `deleted`.
  Firma: `protected static function booted(): void`
- **[registration](../app/Models/ThreeCushionStageResult.php#L80)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registration()`
- **[player](../app/Models/ThreeCushionStageResult.php#L85)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`
- **[category](../app/Models/ThreeCushionStageResult.php#L90)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function category()`

### Models/Tournament.php

[Abrir archivo completo](../app/Models/Tournament.php)

- **[booted](../app/Models/Tournament.php#L14)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `query`.
  Firma: `protected static function booted(): void`
- **[discipline](../app/Models/Tournament.php#L76)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline()`
- **[type](../app/Models/Tournament.php#L81)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function type()`
- **[federation](../app/Models/Tournament.php#L86)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function federation()`
- **[category](../app/Models/Tournament.php#L91)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function category()`
- **[registrations](../app/Models/Tournament.php#L96)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registrations()`
- **[slots](../app/Models/Tournament.php#L101)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function slots()`
- **[categoryPrices](../app/Models/Tournament.php#L106)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function categoryPrices()`
- **[tournamentModalities](../app/Models/Tournament.php#L111)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentModalities()`
- **[venue](../app/Models/Tournament.php#L116)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function venue()`
- **[organizerClub](../app/Models/Tournament.php#L121)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function organizerClub(): BelongsTo`
- **[contacts](../app/Models/Tournament.php#L126)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function contacts()`
- **[fiveQuillasCompetition](../app/Models/Tournament.php#L131)** — Define una relación Eloquent `hasOne`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function fiveQuillasCompetition(): HasOne`
- **[externalVenueState](../app/Models/Tournament.php#L136)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function externalVenueState(): BelongsTo`
- **[hasAssignedVenue](../app/Models/Tournament.php#L141)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function hasAssignedVenue(): bool`
- **[venueName](../app/Models/Tournament.php#L148)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function venueName(): string`
- **[venueStateId](../app/Models/Tournament.php#L155)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function venueStateId(): ?int`
- **[venueStateName](../app/Models/Tournament.php#L162)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function venueStateName(): ?string`
- **[venueAddress](../app/Models/Tournament.php#L169)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `loadMissing`.
  Firma: `public function venueAddress(): string`
- **[hasCompleteVenueAddress](../app/Models/Tournament.php#L190)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function hasCompleteVenueAddress(): bool`
- **[regulationAudits](../app/Models/Tournament.php#L201)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function regulationAudits()`
- **[latestSuccessfulRegulationAudit](../app/Models/Tournament.php#L206)** — Define una relación Eloquent `hasOne`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function latestSuccessfulRegulationAudit(): HasOne`
- **[regulatoryOverrideUser](../app/Models/Tournament.php#L213)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function regulatoryOverrideUser()`
- **[publicationLogoPath](../app/Models/Tournament.php#L218)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `loadMissing`.
  Firma: `public function publicationLogoPath(): ?string`
- **[publicationOrganizationName](../app/Models/Tournament.php#L244)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `loadMissing`.
  Firma: `public function publicationOrganizationName(): string`
- **[calendarStatus](../app/Models/Tournament.php#L271)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function calendarStatus(?\Carbon\CarbonInterface $reference = null): string`
- **[scopeAvailableForRegistration](../app/Models/Tournament.php#L285)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function scopeAvailableForRegistration($query)`
- **[isRegistrationOpen](../app/Models/Tournament.php#L294)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function isRegistrationOpen(): bool`
- **[player](../app/Models/Tournament.php#L306)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`

### Models/TournamentCategoryPrice.php

[Abrir archivo completo](../app/Models/TournamentCategoryPrice.php)

- **[booted](../app/Models/TournamentCategoryPrice.php#L23)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `find`.
  Firma: `protected static function booted(): void`
- **[tournament](../app/Models/TournamentCategoryPrice.php#L32)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament()`
- **[category](../app/Models/TournamentCategoryPrice.php#L37)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function category()`
- **[tournamentModality](../app/Models/TournamentCategoryPrice.php#L42)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentModality()`

### Models/TournamentContact.php

[Abrir archivo completo](../app/Models/TournamentContact.php)

- **[tournament](../app/Models/TournamentContact.php#L12)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament(): BelongsTo`

### Models/TournamentModality.php

[Abrir archivo completo](../app/Models/TournamentModality.php)

- **[tournament](../app/Models/TournamentModality.php#L13)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament()`
- **[modality](../app/Models/TournamentModality.php#L18)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function modality()`
- **[categoryPrices](../app/Models/TournamentModality.php#L23)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function categoryPrices()`
- **[slots](../app/Models/TournamentModality.php#L28)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function slots()`
- **[registrations](../app/Models/TournamentModality.php#L33)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registrations()`
- **[booted](../app/Models/TournamentModality.php#L38)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saved`, `deleted`.
  Firma: `protected static function booted(): void`

### Models/TournamentRegistration.php

[Abrir archivo completo](../app/Models/TournamentRegistration.php)

- **[tournament](../app/Models/TournamentRegistration.php#L44)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament()`
- **[bankTransferOrderItems](../app/Models/TournamentRegistration.php#L49)** — Define una relación Eloquent `morphMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function bankTransferOrderItems()`
- **[tournamentModality](../app/Models/TournamentRegistration.php#L54)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentModality()`
- **[participants](../app/Models/TournamentRegistration.php#L59)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function participants()`
- **[participantIds](../app/Models/TournamentRegistration.php#L64)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `relationLoaded`, `participants`.
  Firma: `public function participantIds(): array`
- **[getParticipantNamesAttribute](../app/Models/TournamentRegistration.php#L87)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `relationLoaded`, `participants`.
  Firma: `public function getParticipantNamesAttribute(): string`
- **[slot](../app/Models/TournamentRegistration.php#L108)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function slot()`
- **[player](../app/Models/TournamentRegistration.php#L113)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`
- **[partner](../app/Models/TournamentRegistration.php#L118)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function partner()`
- **[tournamentInstance](../app/Models/TournamentRegistration.php#L123)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentInstance()`
- **[booted](../app/Models/TournamentRegistration.php#L128)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `query`, `withMessages`, `find`, `saved`, `deleting`, `hasTable`, `deleted`.
  Firma: `protected static function booted()`
- **[calculatePoints](../app/Models/TournamentRegistration.php#L373)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `loadMissing`, `withMessages`.
  Firma: `public function calculatePoints(): float`
- **[threeCushionStageResult](../app/Models/TournamentRegistration.php#L406)** — Define una relación Eloquent `hasOne`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function threeCushionStageResult()`

### Models/TournamentRegistrationParticipant.php

[Abrir archivo completo](../app/Models/TournamentRegistrationParticipant.php)

- **[registration](../app/Models/TournamentRegistrationParticipant.php#L11)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registration()`
- **[player](../app/Models/TournamentRegistrationParticipant.php#L16)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function player()`

### Models/TournamentRegulationAudit.php

[Abrir archivo completo](../app/Models/TournamentRegulationAudit.php)

- **[tournament](../app/Models/TournamentRegulationAudit.php#L29)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament(): BelongsTo`
- **[user](../app/Models/TournamentRegulationAudit.php#L34)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function user(): BelongsTo`

### Models/TournamentRegulationSetting.php

[Abrir archivo completo](../app/Models/TournamentRegulationSetting.php)

- **[discipline](../app/Models/TournamentRegulationSetting.php#L36)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline(): BelongsTo`

### Models/TournamentSlot.php

[Abrir archivo completo](../app/Models/TournamentSlot.php)

- **[booted](../app/Models/TournamentSlot.php#L28)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `saving`, `find`.
  Firma: `protected static function booted(): void`
- **[tournament](../app/Models/TournamentSlot.php#L37)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournament()`
- **[registrations](../app/Models/TournamentSlot.php#L42)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function registrations()`
- **[tournamentModality](../app/Models/TournamentSlot.php#L47)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournamentModality()`
- **[occupiedPlaces](../app/Models/TournamentSlot.php#L52)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `registrations`.
  Firma: `public function occupiedPlaces(): int`

### Models/TournamentType.php

[Abrir archivo completo](../app/Models/TournamentType.php)

- **[booted](../app/Models/TournamentType.php#L41)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `updated`.
  Firma: `protected static function booted(): void`
- **[discipline](../app/Models/TournamentType.php#L51)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function discipline(): BelongsTo`
- **[tournaments](../app/Models/TournamentType.php#L56)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function tournaments(): HasMany`
- **[publicationFederation](../app/Models/TournamentType.php#L61)** — Define una relación Eloquent `belongsTo`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function publicationFederation(): BelongsTo`
- **[usesFiveQuillasStages](../app/Models/TournamentType.php#L66)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `relationLoaded`, `load`.
  Firma: `public function usesFiveQuillasStages(): bool`

### Models/User.php

[Abrir archivo completo](../app/Models/User.php)

- **[casts](../app/Models/User.php#L46)** — Get the attributes that should be cast.
  Firma: `protected function casts(): array`
- **[canAccessPanel](../app/Models/User.php#L54)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `pluck`, `hasAnyRole`, `hasPermissionInAnyDiscipline`.
  Firma: `public function canAccessPanel(Panel $panel): bool`
- **[disciplineAssignments](../app/Models/User.php#L73)** — Define una relación Eloquent `hasMany`; consultar el modelo y claves relacionados en el cuerpo.
  Firma: `public function disciplineAssignments()`
- **[hasActiveDisciplineAssignments](../app/Models/User.php#L78)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineAssignments`.
  Firma: `public function hasActiveDisciplineAssignments(): bool`
- **[allowedDisciplineIds](../app/Models/User.php#L83)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineAssignments`.
  Firma: `public function allowedDisciplineIds(?string $permission = null): array`
- **[hasPermissionInAnyDiscipline](../app/Models/User.php#L98)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineAssignments`.
  Firma: `public function hasPermissionInAnyDiscipline(string $permission): bool`
- **[canGloballyOrInAnyDiscipline](../app/Models/User.php#L106)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `hasActiveDisciplineAssignments`, `hasPermissionInAnyDiscipline`, `can`.
  Firma: `public function canGloballyOrInAnyDiscipline(string $permission): bool`
- **[canGloballyOrForDiscipline](../app/Models/User.php#L113)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `hasRole`, `hasActiveDisciplineAssignments`, `hasDisciplinePermission`, `can`.
  Firma: `public function canGloballyOrForDiscipline(string $permission, int|string|null $disciplineId): bool`
- **[hasDisciplinePermission](../app/Models/User.php#L124)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineAssignments`.
  Firma: `public function hasDisciplinePermission(string $permission, int|string|null $disciplineId): bool`
- **[defaultDisciplineId](../app/Models/User.php#L133)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `allowedDisciplineIds`.
  Firma: `public function defaultDisciplineId(?string $permission = null): ?int`
- **[allowedClubIds](../app/Models/User.php#L140)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `disciplineAssignments`.
  Firma: `public function allowedClubIds(string $permission, int|string|null $disciplineId = null): array`
- **[hasClubScopeForDiscipline](../app/Models/User.php#L154)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `hasRole`, `hasActiveDisciplineAssignments`, `disciplineAssignments`.
  Firma: `public function hasClubScopeForDiscipline(int|string|null $disciplineId, string $permission): bool`
- **[canAccessClubInDiscipline](../app/Models/User.php#L169)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `hasRole`, `hasActiveDisciplineAssignments`, `can`, `disciplineAssignments`.
  Firma: `public function canAccessClubInDiscipline( string $permission, int|string|null $disciplineId, int|string|null $clubId, ): bool`
- **[defaultClubId](../app/Models/User.php#L200)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `allowedClubIds`.
  Firma: `public function defaultClubId(string $permission, int|string|null $disciplineId = null): ?int`
- **[canAccessTournament](../app/Models/User.php#L207)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `canAccessClubInDiscipline`.
  Firma: `public function canAccessTournament(string $permission, ?Tournament $tournament): bool`
- **[scopeDisciplineQuery](../app/Models/User.php#L220)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `hasRole`, `hasActiveDisciplineAssignments`, `allowedDisciplineIds`.
  Firma: `public function scopeDisciplineQuery($query, string $permission)`


## Notifications

### Notifications/SystemActivityEmailNotification.php

[Abrir archivo completo](../app/Notifications/SystemActivityEmailNotification.php)

- **[__construct](../app/Notifications/SystemActivityEmailNotification.php#L13)** — Constructor de la clase; sus parámetros y operaciones de inicialización se encuentran en el enlace.
  Firma: `public function __construct( public readonly string $title, public readonly string $message, )`
- **[via](../app/Notifications/SystemActivityEmailNotification.php#L18)** — Selecciona los canales de notificación.
  Firma: `public function via(object $notifiable): array`
- **[toMail](../app/Notifications/SystemActivityEmailNotification.php#L23)** — Construye el mensaje de correo de la notificación.
  Firma: `public function toMail(object $notifiable): MailMessage`


## Policies

### Policies/CategoryPolicy.php

[Abrir archivo completo](../app/Policies/CategoryPolicy.php)

- **[viewAny](../app/Policies/CategoryPolicy.php#L14)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $user): bool`
- **[view](../app/Policies/CategoryPolicy.php#L19)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $user, Category $category): bool`
- **[create](../app/Policies/CategoryPolicy.php#L24)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $user): bool`
- **[update](../app/Policies/CategoryPolicy.php#L29)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $user, Category $category): bool`
- **[delete](../app/Policies/CategoryPolicy.php#L34)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $user, Category $category): bool`

### Policies/CityPolicy.php

[Abrir archivo completo](../app/Policies/CityPolicy.php)

- **[viewAny](../app/Policies/CityPolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(AuthUser $authUser): bool`
- **[view](../app/Policies/CityPolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(AuthUser $authUser, City $city): bool`
- **[create](../app/Policies/CityPolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(AuthUser $authUser): bool`
- **[update](../app/Policies/CityPolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(AuthUser $authUser, City $city): bool`
- **[delete](../app/Policies/CityPolicy.php#L35)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(AuthUser $authUser, City $city): bool`
- **[restore](../app/Policies/CityPolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(AuthUser $authUser, City $city): bool`
- **[forceDelete](../app/Policies/CityPolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(AuthUser $authUser, City $city): bool`
- **[forceDeleteAny](../app/Policies/CityPolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(AuthUser $authUser): bool`
- **[restoreAny](../app/Policies/CityPolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(AuthUser $authUser): bool`
- **[replicate](../app/Policies/CityPolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(AuthUser $authUser, City $city): bool`
- **[reorder](../app/Policies/CityPolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(AuthUser $authUser): bool`

### Policies/ClubPolicy.php

[Abrir archivo completo](../app/Policies/ClubPolicy.php)

- **[viewAny](../app/Policies/ClubPolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(AuthUser $authUser): bool`
- **[view](../app/Policies/ClubPolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(AuthUser $authUser, Club $club): bool`
- **[create](../app/Policies/ClubPolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(AuthUser $authUser): bool`
- **[update](../app/Policies/ClubPolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(AuthUser $authUser, Club $club): bool`
- **[delete](../app/Policies/ClubPolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(AuthUser $authUser, Club $club): bool`
- **[restore](../app/Policies/ClubPolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(AuthUser $authUser, Club $club): bool`
- **[forceDelete](../app/Policies/ClubPolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(AuthUser $authUser, Club $club): bool`
- **[forceDeleteAny](../app/Policies/ClubPolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(AuthUser $authUser): bool`
- **[restoreAny](../app/Policies/ClubPolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(AuthUser $authUser): bool`
- **[replicate](../app/Policies/ClubPolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(AuthUser $authUser, Club $club): bool`
- **[reorder](../app/Policies/ClubPolicy.php#L70)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(AuthUser $authUser): bool`

### Policies/Concerns/ChecksDisciplinePermissions.php

[Abrir archivo completo](../app/Policies/Concerns/ChecksDisciplinePermissions.php)

- **[allowsAny](../app/Policies/Concerns/ChecksDisciplinePermissions.php#L9)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `protected function allowsAny(User $user, string $permission): bool`
- **[allowsFor](../app/Policies/Concerns/ChecksDisciplinePermissions.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `protected function allowsFor(User $user, string $permission, int|string|null $disciplineId): bool`

### Policies/CountryPolicy.php

[Abrir archivo completo](../app/Policies/CountryPolicy.php)

- **[viewAny](../app/Policies/CountryPolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(AuthUser $authUser): bool`
- **[view](../app/Policies/CountryPolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(AuthUser $authUser, Country $country): bool`
- **[create](../app/Policies/CountryPolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(AuthUser $authUser): bool`
- **[update](../app/Policies/CountryPolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(AuthUser $authUser, Country $country): bool`
- **[delete](../app/Policies/CountryPolicy.php#L35)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(AuthUser $authUser, Country $country): bool`
- **[restore](../app/Policies/CountryPolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(AuthUser $authUser, Country $country): bool`
- **[forceDelete](../app/Policies/CountryPolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(AuthUser $authUser, Country $country): bool`
- **[forceDeleteAny](../app/Policies/CountryPolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(AuthUser $authUser): bool`
- **[restoreAny](../app/Policies/CountryPolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(AuthUser $authUser): bool`
- **[replicate](../app/Policies/CountryPolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(AuthUser $authUser, Country $country): bool`
- **[reorder](../app/Policies/CountryPolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(AuthUser $authUser): bool`

### Policies/DisciplineModalityPolicy.php

[Abrir archivo completo](../app/Policies/DisciplineModalityPolicy.php)

- **[viewAny](../app/Policies/DisciplineModalityPolicy.php#L13)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $user): bool`
- **[view](../app/Policies/DisciplineModalityPolicy.php#L18)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $user, DisciplineModality $record): bool`
- **[create](../app/Policies/DisciplineModalityPolicy.php#L23)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $user): bool`
- **[update](../app/Policies/DisciplineModalityPolicy.php#L28)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $user, DisciplineModality $record): bool`
- **[delete](../app/Policies/DisciplineModalityPolicy.php#L33)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $user, DisciplineModality $record): bool`

### Policies/DisciplinePolicy.php

[Abrir archivo completo](../app/Policies/DisciplinePolicy.php)

- **[viewAny](../app/Policies/DisciplinePolicy.php#L16)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $authUser): bool`
- **[view](../app/Policies/DisciplinePolicy.php#L21)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $authUser, Discipline $discipline): bool`
- **[create](../app/Policies/DisciplinePolicy.php#L26)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $authUser): bool`
- **[update](../app/Policies/DisciplinePolicy.php#L31)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $authUser, Discipline $discipline): bool`
- **[delete](../app/Policies/DisciplinePolicy.php#L36)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $authUser, Discipline $discipline): bool`
- **[restore](../app/Policies/DisciplinePolicy.php#L41)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(User $authUser, Discipline $discipline): bool`
- **[forceDelete](../app/Policies/DisciplinePolicy.php#L46)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(User $authUser, Discipline $discipline): bool`
- **[forceDeleteAny](../app/Policies/DisciplinePolicy.php#L51)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(User $authUser): bool`
- **[restoreAny](../app/Policies/DisciplinePolicy.php#L56)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(User $authUser): bool`
- **[replicate](../app/Policies/DisciplinePolicy.php#L61)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(User $authUser, Discipline $discipline): bool`
- **[reorder](../app/Policies/DisciplinePolicy.php#L66)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(User $authUser): bool`

### Policies/FederationPolicy.php

[Abrir archivo completo](../app/Policies/FederationPolicy.php)

- **[viewAny](../app/Policies/FederationPolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(AuthUser $authUser): bool`
- **[view](../app/Policies/FederationPolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(AuthUser $authUser, Federation $federation): bool`
- **[create](../app/Policies/FederationPolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(AuthUser $authUser): bool`
- **[update](../app/Policies/FederationPolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(AuthUser $authUser, Federation $federation): bool`
- **[delete](../app/Policies/FederationPolicy.php#L35)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(AuthUser $authUser, Federation $federation): bool`
- **[restore](../app/Policies/FederationPolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(AuthUser $authUser, Federation $federation): bool`
- **[forceDelete](../app/Policies/FederationPolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(AuthUser $authUser, Federation $federation): bool`
- **[forceDeleteAny](../app/Policies/FederationPolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(AuthUser $authUser): bool`
- **[restoreAny](../app/Policies/FederationPolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(AuthUser $authUser): bool`
- **[replicate](../app/Policies/FederationPolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(AuthUser $authUser, Federation $federation): bool`
- **[reorder](../app/Policies/FederationPolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(AuthUser $authUser): bool`

### Policies/PlayerPolicy.php

[Abrir archivo completo](../app/Policies/PlayerPolicy.php)

- **[viewAny](../app/Policies/PlayerPolicy.php#L16)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $authUser): bool`
- **[view](../app/Policies/PlayerPolicy.php#L21)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $authUser, Player $player): bool`
- **[create](../app/Policies/PlayerPolicy.php#L26)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $authUser): bool`
- **[update](../app/Policies/PlayerPolicy.php#L31)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $authUser, Player $player): bool`
- **[delete](../app/Policies/PlayerPolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $authUser, Player $player): bool`
- **[restore](../app/Policies/PlayerPolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(User $authUser, Player $player): bool`
- **[forceDelete](../app/Policies/PlayerPolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(User $authUser, Player $player): bool`
- **[forceDeleteAny](../app/Policies/PlayerPolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(User $authUser): bool`
- **[restoreAny](../app/Policies/PlayerPolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(User $authUser): bool`
- **[replicate](../app/Policies/PlayerPolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(User $authUser, Player $player): bool`
- **[reorder](../app/Policies/PlayerPolicy.php#L70)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(User $authUser): bool`

### Policies/RolePolicy.php

[Abrir archivo completo](../app/Policies/RolePolicy.php)

- **[viewAny](../app/Policies/RolePolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(AuthUser $authUser): bool`
- **[view](../app/Policies/RolePolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(AuthUser $authUser, Role $role): bool`
- **[create](../app/Policies/RolePolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(AuthUser $authUser): bool`
- **[update](../app/Policies/RolePolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(AuthUser $authUser, Role $role): bool`
- **[delete](../app/Policies/RolePolicy.php#L35)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(AuthUser $authUser, Role $role): bool`
- **[restore](../app/Policies/RolePolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(AuthUser $authUser, Role $role): bool`
- **[forceDelete](../app/Policies/RolePolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(AuthUser $authUser, Role $role): bool`
- **[forceDeleteAny](../app/Policies/RolePolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(AuthUser $authUser): bool`
- **[restoreAny](../app/Policies/RolePolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(AuthUser $authUser): bool`
- **[replicate](../app/Policies/RolePolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(AuthUser $authUser, Role $role): bool`
- **[reorder](../app/Policies/RolePolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(AuthUser $authUser): bool`

### Policies/StatePolicy.php

[Abrir archivo completo](../app/Policies/StatePolicy.php)

- **[viewAny](../app/Policies/StatePolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(AuthUser $authUser): bool`
- **[view](../app/Policies/StatePolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(AuthUser $authUser, State $state): bool`
- **[create](../app/Policies/StatePolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(AuthUser $authUser): bool`
- **[update](../app/Policies/StatePolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(AuthUser $authUser, State $state): bool`
- **[delete](../app/Policies/StatePolicy.php#L35)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(AuthUser $authUser, State $state): bool`
- **[restore](../app/Policies/StatePolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(AuthUser $authUser, State $state): bool`
- **[forceDelete](../app/Policies/StatePolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(AuthUser $authUser, State $state): bool`
- **[forceDeleteAny](../app/Policies/StatePolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(AuthUser $authUser): bool`
- **[restoreAny](../app/Policies/StatePolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(AuthUser $authUser): bool`
- **[replicate](../app/Policies/StatePolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(AuthUser $authUser, State $state): bool`
- **[reorder](../app/Policies/StatePolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(AuthUser $authUser): bool`

### Policies/TournamentInstancePolicy.php

[Abrir archivo completo](../app/Policies/TournamentInstancePolicy.php)

- **[viewAny](../app/Policies/TournamentInstancePolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(AuthUser $authUser): bool`
- **[view](../app/Policies/TournamentInstancePolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(AuthUser $authUser, TournamentInstance $tournamentInstance): bool`
- **[create](../app/Policies/TournamentInstancePolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(AuthUser $authUser): bool`
- **[update](../app/Policies/TournamentInstancePolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(AuthUser $authUser, TournamentInstance $tournamentInstance): bool`
- **[delete](../app/Policies/TournamentInstancePolicy.php#L35)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(AuthUser $authUser, TournamentInstance $tournamentInstance): bool`
- **[restore](../app/Policies/TournamentInstancePolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(AuthUser $authUser, TournamentInstance $tournamentInstance): bool`
- **[forceDelete](../app/Policies/TournamentInstancePolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(AuthUser $authUser, TournamentInstance $tournamentInstance): bool`
- **[forceDeleteAny](../app/Policies/TournamentInstancePolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(AuthUser $authUser): bool`
- **[restoreAny](../app/Policies/TournamentInstancePolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(AuthUser $authUser): bool`
- **[replicate](../app/Policies/TournamentInstancePolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(AuthUser $authUser, TournamentInstance $tournamentInstance): bool`
- **[reorder](../app/Policies/TournamentInstancePolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(AuthUser $authUser): bool`

### Policies/TournamentPolicy.php

[Abrir archivo completo](../app/Policies/TournamentPolicy.php)

- **[viewAny](../app/Policies/TournamentPolicy.php#L16)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $authUser): bool`
- **[view](../app/Policies/TournamentPolicy.php#L21)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $authUser, Tournament $tournament): bool`
- **[create](../app/Policies/TournamentPolicy.php#L26)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $authUser): bool`
- **[update](../app/Policies/TournamentPolicy.php#L31)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $authUser, Tournament $tournament): bool`
- **[delete](../app/Policies/TournamentPolicy.php#L40)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $authUser, Tournament $tournament): bool`
- **[restore](../app/Policies/TournamentPolicy.php#L45)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(User $authUser, Tournament $tournament): bool`
- **[forceDelete](../app/Policies/TournamentPolicy.php#L50)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(User $authUser, Tournament $tournament): bool`
- **[forceDeleteAny](../app/Policies/TournamentPolicy.php#L55)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(User $authUser): bool`
- **[restoreAny](../app/Policies/TournamentPolicy.php#L60)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(User $authUser): bool`
- **[replicate](../app/Policies/TournamentPolicy.php#L65)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(User $authUser, Tournament $tournament): bool`
- **[reorder](../app/Policies/TournamentPolicy.php#L70)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(User $authUser): bool`
- **[clubId](../app/Policies/TournamentPolicy.php#L75)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `private function clubId(Tournament $tournament): ?int`

### Policies/TournamentRegistrationPolicy.php

[Abrir archivo completo](../app/Policies/TournamentRegistrationPolicy.php)

- **[viewAny](../app/Policies/TournamentRegistrationPolicy.php#L16)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $user): bool`
- **[view](../app/Policies/TournamentRegistrationPolicy.php#L21)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $user, TournamentRegistration $registration): bool`
- **[create](../app/Policies/TournamentRegistrationPolicy.php#L26)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $user): bool`
- **[update](../app/Policies/TournamentRegistrationPolicy.php#L31)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $user, TournamentRegistration $registration): bool`
- **[delete](../app/Policies/TournamentRegistrationPolicy.php#L36)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $user, TournamentRegistration $registration): bool`
- **[restore](../app/Policies/TournamentRegistrationPolicy.php#L41)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(User $user, TournamentRegistration $registration): bool`
- **[forceDelete](../app/Policies/TournamentRegistrationPolicy.php#L46)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(User $user, TournamentRegistration $registration): bool`
- **[forceDeleteAny](../app/Policies/TournamentRegistrationPolicy.php#L51)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(User $user): bool`
- **[restoreAny](../app/Policies/TournamentRegistrationPolicy.php#L56)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(User $user): bool`
- **[replicate](../app/Policies/TournamentRegistrationPolicy.php#L61)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(User $user, TournamentRegistration $registration): bool`
- **[reorder](../app/Policies/TournamentRegistrationPolicy.php#L66)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(User $user): bool`
- **[allowsRegistration](../app/Policies/TournamentRegistrationPolicy.php#L71)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `private function allowsRegistration(User $user, string $permission, TournamentRegistration $registration): bool`

### Policies/TournamentRegulationAuditPolicy.php

[Abrir archivo completo](../app/Policies/TournamentRegulationAuditPolicy.php)

- **[viewAny](../app/Policies/TournamentRegulationAuditPolicy.php#L10)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $user): bool`
- **[view](../app/Policies/TournamentRegulationAuditPolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $user, TournamentRegulationAudit $record): bool`
- **[create](../app/Policies/TournamentRegulationAuditPolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $user): bool`
- **[update](../app/Policies/TournamentRegulationAuditPolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $user, TournamentRegulationAudit $record): bool`
- **[delete](../app/Policies/TournamentRegulationAuditPolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $user, TournamentRegulationAudit $record): bool`

### Policies/TournamentRegulationSettingPolicy.php

[Abrir archivo completo](../app/Policies/TournamentRegulationSettingPolicy.php)

- **[viewAny](../app/Policies/TournamentRegulationSettingPolicy.php#L10)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $user): bool`
- **[view](../app/Policies/TournamentRegulationSettingPolicy.php#L15)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $user, TournamentRegulationSetting $record): bool`
- **[create](../app/Policies/TournamentRegulationSettingPolicy.php#L20)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $user): bool`
- **[update](../app/Policies/TournamentRegulationSettingPolicy.php#L25)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $user, TournamentRegulationSetting $record): bool`
- **[delete](../app/Policies/TournamentRegulationSettingPolicy.php#L30)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $user, TournamentRegulationSetting $record): bool`

### Policies/TournamentTypePolicy.php

[Abrir archivo completo](../app/Policies/TournamentTypePolicy.php)

- **[viewAny](../app/Policies/TournamentTypePolicy.php#L16)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(User $authUser): bool`
- **[view](../app/Policies/TournamentTypePolicy.php#L21)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(User $authUser, TournamentType $tournamentType): bool`
- **[create](../app/Policies/TournamentTypePolicy.php#L26)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(User $authUser): bool`
- **[update](../app/Policies/TournamentTypePolicy.php#L31)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(User $authUser, TournamentType $tournamentType): bool`
- **[delete](../app/Policies/TournamentTypePolicy.php#L36)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(User $authUser, TournamentType $tournamentType): bool`
- **[restore](../app/Policies/TournamentTypePolicy.php#L41)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(User $authUser, TournamentType $tournamentType): bool`
- **[forceDelete](../app/Policies/TournamentTypePolicy.php#L46)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(User $authUser, TournamentType $tournamentType): bool`
- **[forceDeleteAny](../app/Policies/TournamentTypePolicy.php#L51)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(User $authUser): bool`
- **[restoreAny](../app/Policies/TournamentTypePolicy.php#L56)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(User $authUser): bool`
- **[replicate](../app/Policies/TournamentTypePolicy.php#L61)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(User $authUser, TournamentType $tournamentType): bool`
- **[reorder](../app/Policies/TournamentTypePolicy.php#L66)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(User $authUser): bool`

### Policies/UserPolicy.php

[Abrir archivo completo](../app/Policies/UserPolicy.php)

- **[viewAny](../app/Policies/UserPolicy.php#L12)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function viewAny(?User $authUser): bool`
- **[view](../app/Policies/UserPolicy.php#L17)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function view(?User $authUser): bool`
- **[create](../app/Policies/UserPolicy.php#L22)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function create(?User $authUser): bool`
- **[update](../app/Policies/UserPolicy.php#L27)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function update(?User $authUser): bool`
- **[delete](../app/Policies/UserPolicy.php#L32)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function delete(?User $authUser): bool`
- **[restore](../app/Policies/UserPolicy.php#L37)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restore(?User $authUser): bool`
- **[forceDelete](../app/Policies/UserPolicy.php#L42)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDelete(?User $authUser): bool`
- **[forceDeleteAny](../app/Policies/UserPolicy.php#L47)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function forceDeleteAny(?User $authUser): bool`
- **[restoreAny](../app/Policies/UserPolicy.php#L52)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function restoreAny(?User $authUser): bool`
- **[replicate](../app/Policies/UserPolicy.php#L57)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function replicate(?User $authUser): bool`
- **[reorder](../app/Policies/UserPolicy.php#L62)** — Evalúa la autorización de esta operación sobre el recurso; revisar las condiciones y el alcance por disciplina en el cuerpo.
  Firma: `public function reorder(?User $authUser): bool`


## Providers

### Providers/AppServiceProvider.php

[Abrir archivo completo](../app/Providers/AppServiceProvider.php)

- **[register](../app/Providers/AppServiceProvider.php#L21)** — Register any application services.
  Firma: `public function register(): void`
- **[boot](../app/Providers/AppServiceProvider.php#L29)** — Bootstrap any application services.
  Firma: `public function boot(): void`

### Providers/Filament/AdminPanelProvider.php

[Abrir archivo completo](../app/Providers/Filament/AdminPanelProvider.php)

- **[panel](../app/Providers/Filament/AdminPanelProvider.php#L29)** — Configura identidad acceso middleware páginas y recursos del panel.
  Firma: `public function panel(Panel $panel): Panel`

### Providers/Filament/GuestPanelProvider.php

[Abrir archivo completo](../app/Providers/Filament/GuestPanelProvider.php)

- **[panel](../app/Providers/Filament/GuestPanelProvider.php#L27)** — Configura identidad acceso middleware páginas y recursos del panel.
  Firma: `public function panel(Panel $panel): Panel`
- **[afterBoot](../app/Providers/Filament/GuestPanelProvider.php#L69)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `serving`, `auth`.
  Firma: `public function afterBoot(): void`


## Services

### Services/AdminNotifier.php

[Abrir archivo completo](../app/Services/AdminNotifier.php)

- **[notifyBulkAction](../app/Services/AdminNotifier.php#L18)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function notifyBulkAction( mixed $action, string $operation, string|array $displayFields = 'name', string $resourceName = 'registros', ): mixed`
- **[notifyAction](../app/Services/AdminNotifier.php#L32)** — Construye la acción Filament y sus callbacks. Sus campos validaciones y operaciones están definidos en este método.
  Firma: `public static function notifyAction( mixed $action, ?Page $pageInstance, string $operation, string|array $displayFields = 'name', ?string $customResourceName = null, bool $sendEmail = true, ): mixed`
- **[send](../app/Services/AdminNotifier.php#L57)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `getResource`, `getNavigationLabel`, `recipients`, `make`, `sendEmail`.
  Firma: `public static function send( ?Page $pageInstance, Model $record, string $operation, string|array $displayFields = 'name', ?string $customResourceName = null, bool $sendEmail = true, ): void`
- **[recipients](../app/Services/AdminNotifier.php#L105)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `query`.
  Firma: `public static function recipients(): Collection`
- **[recipientEmails](../app/Services/AdminNotifier.php#L122)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `recipients`.
  Firma: `public static function recipientEmails(): array`
- **[sendBulk](../app/Services/AdminNotifier.php#L132)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `recipients`, `make`, `sendEmail`.
  Firma: `public static function sendBulk( iterable $records, string $operation, string|array $displayFields = 'name', string $resourceName = 'registros', ): void`
- **[sendException](../app/Services/AdminNotifier.php#L175)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `user`, `recipients`, `make`, `sendEmail`.
  Firma: `public static function sendException(Throwable $e): void`
- **[sendEmail](../app/Services/AdminNotifier.php#L200)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `send`, `error`.
  Firma: `private static function sendEmail(Collection $recipients, SystemActivityEmailNotification $notification): void`

### Services/AnnualMembershipService.php

[Abrir archivo completo](../app/Services/AnnualMembershipService.php)

- **[configure](../app/Services/AnnualMembershipService.php#L27)** — Crea o actualiza la definición anual de una membresía.
  Firma: `public function configure(int $year, int $disciplineId, float $amount, string $dueDate): Membership`
- **[renew](../app/Services/AnnualMembershipService.php#L47)** — Renueva membresías de jugadores y registra las obligaciones resultantes.
  Firma: `public function renew( Membership $membership, Collection $players, ?Club $club, string $payerType = 'players', ?int $payerId = null, ?string $payerName = null, bool $preservePlayerClub = false, ?float $amountPerPlayer = null, ): Collection`
- **[createInstitutionalDebt](../app/Services/AnnualMembershipService.php#L74)** — Crea la deuda de una institución responsable para el período.
  Firma: `public function createInstitutionalDebt( Membership $membership, Collection $players, ?Club $club, bool $preservePlayerClub = false, string $debtorType = 'club', ?int $debtorId = null, ): Collection`
- **[renewManually](../app/Services/AnnualMembershipService.php#L113)** — Registra una renovación manual con su fuente y responsable.
  Firma: `public function renewManually( Membership $membership, Collection $players, ?Club $club, string $payerType, ?int $payerId, string $manualMethod, float $amountPerPlayer, ?string $reference = null, ?string $notes = null, bool $preservePlayerClub = false, ): Collection`
- **[sendConfirmation](../app/Services/AnnualMembershipService.php#L207)** — Notifica la confirmación de las renovaciones procesadas.
  Firma: `public function sendConfirmation(Membership $membership, Collection $renewals, ?User $recipient = null): void`
- **[expireOverdue](../app/Services/AnnualMembershipService.php#L235)** — Marca como vencidas las obligaciones que superaron su fecha límite.
  Firma: `public function expireOverdue(): int`

### Services/BankTransferService.php

[Abrir archivo completo](../app/Services/BankTransferService.php)

- **[createMembershipOrder](../app/Services/BankTransferService.php#L28)** — Crea una orden de transferencia para una o varias renovaciones.
  Firma: `public function createMembershipOrder( Membership $membership, Collection $players, ?Club $club, string $payerType = 'players', ?int $payerId = null, ?string $payerName = null, bool $preservePlayerClub = false, ?float $amountPerPlayer = null, ): BankTransferOrder`
- **[createTournamentRegistrationOrder](../app/Services/BankTransferService.php#L115)** — Crea, si corresponde, la orden de pago de una inscripción.
  Firma: `public function createTournamentRegistrationOrder(TournamentRegistration $registration): ?BankTransferOrder`
- **[submit](../app/Services/BankTransferService.php#L213)** — Presenta la orden y su comprobante para revisión.
  Firma: `public function submit(BankTransferOrder $order): void`
- **[approve](../app/Services/BankTransferService.php#L226)** — Aprueba la transferencia y aplica el pago a su concepto de origen.
  Firma: `public function approve(BankTransferOrder $order): void`
- **[reject](../app/Services/BankTransferService.php#L292)** — Rechaza la transferencia conservando el motivo para auditoría.
  Firma: `public function reject(BankTransferOrder $order, string $notes): void`
- **[deleteOrder](../app/Services/BankTransferService.php#L304)** — Elimina una orden únicamente cuando su estado permite descartarla.
  Firma: `public function deleteOrder(BankTransferOrder $order): void`
- **[requireActiveSetting](../app/Services/BankTransferService.php#L333)** — Obtiene la configuración bancaria activa o informa que no está disponible.
  Firma: `private function requireActiveSetting(): BankTransferSetting`
- **[destinationSnapshot](../app/Services/BankTransferService.php#L343)** — Captura los datos bancarios de destino para conservar el valor histórico.
  Firma: `private function destinationSnapshot(BankTransferSetting|Club $source, string $type, string $name): array`
- **[guardAgainstOpenOrders](../app/Services/BankTransferService.php#L365)** — Impide crear órdenes duplicadas para obligaciones con pagos abiertos.
  Firma: `private function guardAgainstOpenOrders(Collection $renewals): void`
- **[registrationAmount](../app/Services/BankTransferService.php#L381)** — Calcula el importe exigible para una inscripción.
  Firma: `private function registrationAmount(TournamentRegistration $registration): float`
- **[nextCode](../app/Services/BankTransferService.php#L398)** — Genera el próximo código público de orden.
  Firma: `private function nextCode(): string`
- **[notifyParticipants](../app/Services/BankTransferService.php#L406)** — Envía una notificación a los participantes vinculados con la orden.
  Firma: `private function notifyParticipants(BankTransferOrder $order, string $title, string $message): void`

### Services/FiveQuillasCompetitionService.php

[Abrir archivo completo](../app/Services/FiveQuillasCompetitionService.php)

- **[initialize](../app/Services/FiveQuillasCompetitionService.php#L29)** — Crea o recupera la competencia, inicializa sus fases y sincroniza inscripciones aprobadas.
  Firma: `public function initialize(Tournament $tournament): FiveQuillasCompetition`
- **[syncApprovedEntries](../app/Services/FiveQuillasCompetitionService.php#L57)** — Sincroniza participantes aprobados y captura horario, fase de ingreso y ranking de la competencia.
  Firma: `public function syncApprovedEntries(FiveQuillasCompetition $competition): int`
- **[refreshEntryPhaseAssignments](../app/Services/FiveQuillasCompetitionService.php#L127)** — Recalcula la fase de ingreso y elimina overrides de siembra que hayan quedado obsoletos.
  Firma: `public function refreshEntryPhaseAssignments(FiveQuillasCompetition $competition): void`
- **[createManualZones](../app/Services/FiveQuillasCompetitionService.php#L180)** — Guarda las zonas editables de un horario y conserva intactas las que ya tienen actividad deportiva.
  Firma: `public function createManualZones(FiveQuillasCompetition $competition, int $slotId, array $zoneRows): int`
- **[configureFormat](../app/Services/FiveQuillasCompetitionService.php#L273)** — Reemplaza la definición de fases validando dependencias, reglas y orden del formato.
  Firma: `public function configureFormat(FiveQuillasCompetition $competition, array $phaseRows): int`
- **[applyCompetitionTemplate](../app/Services/FiveQuillasCompetitionService.php#L444)** — Aplica una plantilla inicial de competencia al torneo.
  Firma: `public function applyCompetitionTemplate(FiveQuillasCompetition $competition, string $format): int`
- **[scheduleConfiguredMatches](../app/Services/FiveQuillasCompetitionService.php#L479)** — Asigna fecha, hora y mesa a los partidos comprendidos por un bloque de programación.
  Firma: `public function scheduleConfiguredMatches(FiveQuillasPhase $phase, int $scheduleIndex, array $overrides = []): int`
- **[ensureBracketSkeleton](../app/Services/FiveQuillasCompetitionService.php#L564)** — Crea la estructura anticipada de una llave para poder publicarla y programarla antes de conocer todos los clasificados. Los ingresos directos ya conocidos conservan su posición; los rivales faltantes quedan A definir.
  Firma: `public function ensureBracketSkeleton(FiveQuillasPhase $phase): int`
- **[reconcileBracketRounds](../app/Services/FiveQuillasCompetitionService.php#L697)** — Ajusta una grilla existente al número de clasificados configurado.
  Firma: `public function reconcileBracketRounds(FiveQuillasPhase $phase): void`
- **[repairPreviousResultsBracket](../app/Services/FiveQuillasCompetitionService.php#L729)** — Repara una grilla no iniciada ordenada por ranking en lugar de resultados.
  Firma: `public function repairPreviousResultsBracket(FiveQuillasPhase $phase): void`
- **[resolveBracketByes](../app/Services/FiveQuillasCompetitionService.php#L781)** — Resuelve LIBRES reales sólo cuando ya están ubicados todos los participantes.
  Firma: `public function resolveBracketByes(FiveQuillasPhase $phase): void`
- **[configuredTableCountForMatch](../app/Services/FiveQuillasCompetitionService.php#L815)** — Devuelve la cantidad de mesas configurada para el bloque al que pertenece el partido.
  Firma: `public function configuredTableCountForMatch(FiveQuillasMatch $match): int`
- **[availableTableOptions](../app/Services/FiveQuillasCompetitionService.php#L849)** — Lista las mesas que no están ocupadas por otro partido llamado o en juego. El partido actual se excluye para permitir conservar o cambiar su mesa. Las partidas terminadas mantienen la mesa como dato histórico, pero ya no la ocupan.
  Firma: `public function availableTableOptions(FiveQuillasMatch $match): array`
- **[callMatch](../app/Services/FiveQuillasCompetitionService.php#L875)** — Llama un partido y reserva de forma exclusiva una mesa disponible.
  Firma: `public function callMatch(FiveQuillasMatch $match, int $tableNumber): FiveQuillasMatch`
- **[cancelMatchCall](../app/Services/FiveQuillasCompetitionService.php#L923)** — Cancela un llamado que todavía no comenzó y vuelve a liberar la mesa.
  Firma: `public function cancelMatchCall(FiveQuillasMatch $match): FiveQuillasMatch`
- **[preparePhase](../app/Services/FiveQuillasCompetitionService.php#L946)** — Construye los grupos o la llave de una fase usando su método de ingreso configurado.
  Firma: `public function preparePhase(FiveQuillasPhase $phase): int`
- **[drawCandidates](../app/Services/FiveQuillasCompetitionService.php#L1134)** — Devuelve candidatos habilitados para el sorteo o armado manual de la fase.
  Firma: `public function drawCandidates(FiveQuillasPhase $phase)`
- **[bracketParticipantRows](../app/Services/FiveQuillasCompetitionService.php#L1170)** — Obtiene el orden editable de participantes que alimentará la primera ronda.
  Firma: `public function bracketParticipantRows(FiveQuillasPhase $phase): array`
- **[bracketParticipantDisplayRows](../app/Services/FiveQuillasCompetitionService.php#L1265)** — Construye las filas de presentación con posición, origen y criterio histórico.
  Firma: `public function bracketParticipantDisplayRows(FiveQuillasPhase $phase): array`
- **[qualifiedParticipantRows](../app/Services/FiveQuillasCompetitionService.php#L1296)** — Devuelve los clasificados ordenados según el método de ingreso de la fase siguiente.
  Firma: `public function qualifiedParticipantRows(FiveQuillasPhase $phase): array`
- **[compareQualifiedPhasePerformance](../app/Services/FiveQuillasCompetitionService.php#L1435)** — Compara dos clasificados por posición, diferencia de sets y promedio de la fase anterior.
  Firma: `private function compareQualifiedPhasePerformance( $matches, int $leftEntryId, int $rightEntryId, int $leftHistorical, int $rightHistorical, ): int`
- **[bracketMatchupRows](../app/Services/FiveQuillasCompetitionService.php#L1460)** — Proyecta los enfrentamientos de primera ronda a partir de las posiciones de la llave.
  Firma: `public function bracketMatchupRows(FiveQuillasPhase $phase): array`
- **[saveBracketParticipantOrder](../app/Services/FiveQuillasCompetitionService.php#L1493)** — Valida y persiste correcciones de participantes sin alterar los números de posición.
  Firma: `public function saveBracketParticipantOrder(FiveQuillasPhase $phase, array $rows): void`
- **[saveDrawNumbers](../app/Services/FiveQuillasCompetitionService.php#L1543)** — Persiste los números de sorteo y evita duplicados dentro de la fase.
  Firma: `public function saveDrawNumbers(FiveQuillasPhase $phase, array $rows): void`
- **[saveQualifiedDrawNumber](../app/Services/FiveQuillasCompetitionService.php#L1576)** — Guarda un sorteo individual sin reemplazar los números de otros clasificados.
  Firma: `public function saveQualifiedDrawNumber(FiveQuillasPhase $phase, int $entryId, int $number): void`
- **[assignSelectedScoringRule](../app/Services/FiveQuillasCompetitionService.php#L1601)** — Asocia a una entrada la regla de puntuación elegida por la organización.
  Firma: `public function assignSelectedScoringRule(FiveQuillasEntry $entry, string $ruleKey): void`
- **[proposedPointsForPlacement](../app/Services/FiveQuillasCompetitionService.php#L1622)** — Calcula los puntos propuestos para una posición final sin escribir datos.
  Firma: `public function proposedPointsForPlacement(Tournament $tournament, int $place): ?float`
- **[proposedPointsForEntry](../app/Services/FiveQuillasCompetitionService.php#L1635)** — Calcula los puntos propuestos para una entrada considerando instancia y regla asignada.
  Firma: `public function proposedPointsForEntry(Tournament $tournament, FiveQuillasCompetition $competition, FiveQuillasEntry $entry): ?float`
- **[commitFinalScoring](../app/Services/FiveQuillasCompetitionService.php#L1649)** — Confirma las puntuaciones finales de forma idempotente y devuelve un resumen de la operación.
  Firma: `public function commitFinalScoring(FiveQuillasCompetition $competition): array`
- **[createSlotElimination](../app/Services/FiveQuillasCompetitionService.php#L1734)** — Construye automáticamente los cruces por horario desde los clasificados disponibles.
  Firma: `private function createSlotElimination(FiveQuillasPhase $phase, ?FiveQuillasPhase $previous): int`
- **[slotEliminationCandidates](../app/Services/FiveQuillasCompetitionService.php#L1792)** — Devuelve primeros y segundos clasificados disponibles para cruces del mismo horario.
  Firma: `public function slotEliminationCandidates(FiveQuillasPhase $phase)`
- **[previousPhaseFor](../app/Services/FiveQuillasCompetitionService.php#L1808)** — Resuelve la fase anterior explícita o, como respaldo, la fase habilitada precedente.
  Firma: `public function previousPhaseFor(FiveQuillasPhase $phase): ?FiveQuillasPhase`
- **[createManualSlotElimination](../app/Services/FiveQuillasCompetitionService.php#L1816)** — Agrega o actualiza cruces manuales sin borrar partidos ya iniciados o finalizados.
  Firma: `public function createManualSlotElimination(FiveQuillasPhase $phase, array $crossings): int`
- **[scoresWithCrossingMetadata](../app/Services/FiveQuillasCompetitionService.php#L1934)** — Conserva en el resultado los metadatos que identifican el cruce configurado.
  Firma: `private function scoresWithCrossingMetadata(FiveQuillasMatch $match, array $scores): array`
- **[nextPowerOfTwo](../app/Services/FiveQuillasCompetitionService.php#L1946)** — Obtiene la menor potencia de dos capaz de contener la cantidad indicada.
  Firma: `private function nextPowerOfTwo(int $value): int`
- **[skipUnnecessaryCircuitPlayIn](../app/Services/FiveQuillasCompetitionService.php#L1957)** — Prepara sólo los emparejamientos necesarios una vez confirmados los clasificados.
  Firma: `public function skipUnnecessaryCircuitPlayIn(FiveQuillasCompetition $competition): void`
- **[reconcileCircuitPlayIn](../app/Services/FiveQuillasCompetitionService.php#L1965)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `previousPhase`, `circuitCrossingsAreComplete`, `qualifiedEntriesFromPhase`, `createPlayIn`.
  Firma: `private function reconcileCircuitPlayIn(FiveQuillasCompetition $competition): void`
- **[circuitCrossingsAreComplete](../app/Services/FiveQuillasCompetitionService.php#L1995)** — Impide usar clasificaciones parciales de cualquiera de los horarios del circuito.
  Firma: `private function circuitCrossingsAreComplete(FiveQuillasPhase $phase): bool`
- **[loadRegisteredMasters](../app/Services/FiveQuillasCompetitionService.php#L2008)** — Recupera los ingresos históricos del horario Masters sin consultar el ranking actual.
  Firma: `public function loadRegisteredMasters(FiveQuillasPhase $phase): int`
- **[entryPhaseForRegistration](../app/Services/FiveQuillasCompetitionService.php#L2110)** — Determina la fase de ingreso por categoría y horario guardados en la inscripción.
  Firma: `private function entryPhaseForRegistration($phases, ?Player $player, ?string $slotName): ?FiveQuillasPhase`
- **[createPlayIn](../app/Services/FiveQuillasCompetitionService.php#L2157)** — Construye una fase de reclasificación previa a la llave principal.
  Firma: `private function createPlayIn(FiveQuillasPhase $phase, $source): int`
- **[seedBracketEntries](../app/Services/FiveQuillasCompetitionService.php#L2193)** — Ordena las entradas por el método configurado y aplica overrides persistidos.
  Firma: `private function seedBracketEntries(FiveQuillasPhase $phase, $source): array`
- **[createEliminationBracket](../app/Services/FiveQuillasCompetitionService.php#L2251)** — Crea todas las rondas y conexiones de una llave de eliminación directa.
  Firma: `private function createEliminationBracket(FiveQuillasPhase $phase, array $seeds, bool $finalizeByes = true): int`
- **[syncProgressiveBracket](../app/Services/FiveQuillasCompetitionService.php#L2312)** — Completa una llave ya creada a medida que llegan clasificados de fases simultáneas.
  Firma: `private function syncProgressiveBracket(FiveQuillasPhase $phase, array $seeds, bool $finalizeByes): int`
- **[qualifiedEntriesFromPhase](../app/Services/FiveQuillasCompetitionService.php#L2339)** — Obtiene las entradas clasificadas de una fase de eliminación.
  Firma: `private function qualifiedEntriesFromPhase(FiveQuillasPhase $phase)`
- **[qualifiedZoneRows](../app/Services/FiveQuillasCompetitionService.php#L2379)** — Calcula clasificados de zonas con sus métricas y desempates.
  Firma: `private function qualifiedZoneRows(FiveQuillasPhase $phase)`
- **[recordMatchResult](../app/Services/FiveQuillasCompetitionService.php#L2413)** — Valida los sets, calcula ganador y estadísticas, y propaga el resultado.
  Firma: `public function recordMatchResult(FiveQuillasMatch $match, array $scores): FiveQuillasMatch`
- **[resolveAbsentMatch](../app/Services/FiveQuillasCompetitionService.php#L2540)** — Resuelve una incomparecencia manteniendo al ausente como participante real.
  Firma: `public function resolveAbsentMatch(FiveQuillasMatch $match): bool`
- **[declareMatchWinner](../app/Services/FiveQuillasCompetitionService.php#L2581)** — Proclama un ganador y genera los sets computados completos definidos para la fase.
  Firma: `public function declareMatchWinner(FiveQuillasMatch $match, int $winnerEntryId): FiveQuillasMatch`
- **[correctMatchParticipants](../app/Services/FiveQuillasCompetitionService.php#L2632)** — Corrige participantes de un partido no iniciado y revierte propagaciones dependientes.
  Firma: `public function correctMatchParticipants(FiveQuillasMatch $match, ?int $playerOneId, ?int $playerTwoId): FiveQuillasMatch`
- **[finalInstanceForEntry](../app/Services/FiveQuillasCompetitionService.php#L2730)** — Devuelve la etiqueta de la última instancia alcanzada por una entrada.
  Firma: `public function finalInstanceForEntry(FiveQuillasCompetition $competition, FiveQuillasEntry $entry): string`
- **[competitionStageForEntry](../app/Services/FiveQuillasCompetitionService.php#L2743)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `lower`, `contains`.
  Firma: `private function competitionStageForEntry(FiveQuillasCompetition $competition, FiveQuillasEntry $entry): string`
- **[invalidateFollowingUnplayedPhases](../app/Services/FiveQuillasCompetitionService.php#L2782)** — Retira clasificados de estructuras posteriores que todavía no tienen actividad.
  Firma: `private function invalidateFollowingUnplayedPhases(FiveQuillasPhase $phase): void`
- **[resetMatchResult](../app/Services/FiveQuillasCompetitionService.php#L2809)** — Elimina el resultado y reabre la fase sin afectar partidas posteriores disputadas.
  Firma: `public function resetMatchResult(FiveQuillasMatch $match): FiveQuillasMatch`
- **[completePhaseWhenReady](../app/Services/FiveQuillasCompetitionService.php#L2875)** — Completa una fase sólo cuando todos sus horarios y partidos requeridos están resueltos.
  Firma: `private function completePhaseWhenReady(int $phaseId): void`
- **[reopenIncompleteConcurrentPhases](../app/Services/FiveQuillasCompetitionService.php#L2928)** — Reabre fases marcadas completas si aún faltan horarios o cruces simultáneos.
  Firma: `public function reopenIncompleteConcurrentPhases(FiveQuillasCompetition $competition): void`
- **[unassignedCompetitionEntries](../app/Services/FiveQuillasCompetitionService.php#L2972)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function unassignedCompetitionEntries(FiveQuillasCompetition $competition)`
- **[zonesHaveEveryScheduledPlayer](../app/Services/FiveQuillasCompetitionService.php#L2994)** — Comprueba que todos los participantes esperados estén asignados a zonas.
  Firma: `private function zonesHaveEveryScheduledPlayer(FiveQuillasPhase $phase): bool`
- **[slotEliminationHasEveryCrossing](../app/Services/FiveQuillasCompetitionService.php#L3022)** — Comprueba que cada horario tenga todos sus cruces configurados.
  Firma: `private function slotEliminationHasEveryCrossing(FiveQuillasPhase $phase): bool`
- **[recalculateFinalStandings](../app/Services/FiveQuillasCompetitionService.php#L3058)** — Reconstruye posiciones finales desde el recorrido y rendimiento de cada participante.
  Firma: `public function recalculateFinalStandings(FiveQuillasCompetition $competition): void`
- **[ensureFinalStandingsOrder](../app/Services/FiveQuillasCompetitionService.php#L3081)** — Garantiza que las posiciones persistidas formen un orden completo y coherente.
  Firma: `public function ensureFinalStandingsOrder(FiveQuillasCompetition $competition): void`
- **[normalizeByeScores](../app/Services/FiveQuillasCompetitionService.php#L3111)** — Normaliza partidos contra LIBRE con los sets y puntos computados de la fase.
  Firma: `private function normalizeByeScores(FiveQuillasCompetition $competition): void`
- **[finalStandingPerformance](../app/Services/FiveQuillasCompetitionService.php#L3148)** — Resume partidos, sets, puntos y promedio usados para ordenar una entrada.
  Firma: `public function finalStandingPerformance(FiveQuillasCompetition $competition, int $entryId): array`
- **[assignFinalPoints](../app/Services/FiveQuillasCompetitionService.php#L3181)** — Asigna puntuación asociada a puestos finales de la fase.
  Firma: `private function assignFinalPoints(FiveQuillasPhase $phase): void`
- **[assignPhaseEliminationPoints](../app/Services/FiveQuillasCompetitionService.php#L3230)** — Asigna puntuación según la ronda en que cada entrada fue eliminada.
  Firma: `private function assignPhaseEliminationPoints(FiveQuillasPhase $phase): void`
- **[lastRoundPlayed](../app/Services/FiveQuillasCompetitionService.php#L3297)** — Obtiene la última ronda disputada por una entrada.
  Firma: `private function lastRoundPlayed($matches, int $entryId): int`
- **[entryPerformance](../app/Services/FiveQuillasCompetitionService.php#L3310)** — Acumula indicadores deportivos de una entrada sobre una colección de partidos.
  Firma: `private function entryPerformance($matches, int $entryId): array`
- **[orderByPreviousPhaseResult](../app/Services/FiveQuillasCompetitionService.php#L3334)** — Ordena participantes con la posición y rendimiento guardados en la fase anterior.
  Firma: `private function orderByPreviousPhaseResult(FiveQuillasPhase $phase, $source)`
- **[eliminationRoundLabel](../app/Services/FiveQuillasCompetitionService.php#L3362)** — Convierte tamaño de llave y ronda en una etiqueta de instancia legible.
  Firma: `private function eliminationRoundLabel(int $bracketSize, int $round): string`
- **[loserEntryId](../app/Services/FiveQuillasCompetitionService.php#L3379)** — Obtiene el identificador del perdedor de un partido completado.
  Firma: `private function loserEntryId(FiveQuillasMatch $match): ?int`
- **[ruleForPlacement](../app/Services/FiveQuillasCompetitionService.php#L3389)** — Selecciona la regla aplicable a una posición final.
  Firma: `private function ruleForPlacement(array $rules, int $place): ?array`
- **[ruleForEntryPlacement](../app/Services/FiveQuillasCompetitionService.php#L3422)** — Selecciona la regla aplicable según posición e instancia de la entrada.
  Firma: `private function ruleForEntryPlacement(array $rules, FiveQuillasCompetition $competition, FiveQuillasEntry $entry): ?array`
- **[scoringPlacementForEntry](../app/Services/FiveQuillasCompetitionService.php#L3482)** — Los puestos generales son consecutivos, pero LIBRE no representa una eliminación ni permite a la fase anterior ocupar el rango de la llave.
  Firma: `private function scoringPlacementForEntry(FiveQuillasCompetition $competition, FiveQuillasEntry $entry): int`
- **[advanceEntry](../app/Services/FiveQuillasCompetitionService.php#L3539)** — Coloca al ganador en el casillero conectado de la ronda siguiente.
  Firma: `private function advanceEntry(?int $matchId, ?int $position, int $entryId): void`
- **[removeAdvancedEntry](../app/Services/FiveQuillasCompetitionService.php#L3552)** — Retira una propagación anterior cuando cambia el partido de origen.
  Firma: `private function removeAdvancedEntry(?int $matchId, ?int $position, ?int $entryId): void`
- **[resetAutomaticByeChain](../app/Services/FiveQuillasCompetitionService.php#L3569)** — Revierte avances automáticos encadenados originados por lugares LIBRE.
  Firma: `private function resetAutomaticByeChain(int $matchId): void`
- **[resolveReadyByes](../app/Services/FiveQuillasCompetitionService.php#L3615)** — Proclama avances automáticos de partidos que tienen un único participante real.
  Firma: `private function resolveReadyByes($matchIds): void`
- **[recalculateGroupStatistics](../app/Services/FiveQuillasCompetitionService.php#L3647)** — Recalcula desde cero la tabla de una zona a partir de sus partidos.
  Firma: `private function recalculateGroupStatistics(int $groupId): void`
- **[refreshConfiguredSlotCrossings](../app/Services/FiveQuillasCompetitionService.php#L3730)** — Actualiza nombres pendientes de cruces al conocerse los clasificados de zona.
  Firma: `private function refreshConfiguredSlotCrossings(FiveQuillasGroup $group): void`
- **[uniquePhaseCode](../app/Services/FiveQuillasCompetitionService.php#L3763)** — Genera un código de fase estable y no repetido dentro de la competencia.
  Firma: `private function uniquePhaseCode(FiveQuillasCompetition $competition, string $name, $usedCodes): string`
- **[createZoneBracket](../app/Services/FiveQuillasCompetitionService.php#L3777)** — Crea partidos de zona y definiciones de puestos para sus integrantes.
  Firma: `private function createZoneBracket(FiveQuillasPhase $phase, FiveQuillasGroup $group, $entries): void`
- **[resolveBye](../app/Services/FiveQuillasCompetitionService.php#L3820)** — Completa un partido contra LIBRE y propaga al único participante.
  Firma: `private function resolveBye($match): void`
- **[phaseTemplate](../app/Services/FiveQuillasCompetitionService.php#L3863)** — Construye la definición de fases correspondiente al formato inicial elegido.
  Firma: `private function phaseTemplate(Tournament $tournament, string $format = 'zones_final'): array`
- **[officialBracketPairs](../app/Services/FiveQuillasCompetitionService.php#L3930)** — Genera el orden oficial de emparejamientos por semillas para una potencia de dos.
  Firma: `private function officialBracketPairs(int $size): array`
- **[previousPhase](../app/Services/FiveQuillasCompetitionService.php#L3953)** — Obtiene la fase inmediatamente anterior por orden.
  Firma: `private function previousPhase(FiveQuillasPhase $phase): ?FiveQuillasPhase`
- **[generatedBracketPairs](../app/Services/FiveQuillasCompetitionService.php#L3970)** — Genera pares de posiciones cuyo total es el tamaño de llave más uno.
  Firma: `private function generatedBracketPairs(int $size): array`
- **[phase](../app/Services/FiveQuillasCompetitionService.php#L3989)** — Normaliza los atributos necesarios para crear una fase de plantilla.
  Firma: `private function phase(string $code, string $name, string $type, int $order, int $sets, string $seeding, bool $split, array $settings = []): array`
- **[uniqueSlug](../app/Services/FiveQuillasCompetitionService.php#L4014)** — Genera un slug público único para la competencia.
  Firma: `private function uniqueSlug(Tournament $tournament): string`

### Services/PlayerCategoryChangeService.php

[Abrir archivo completo](../app/Services/PlayerCategoryChangeService.php)

- **[scheduleManualChange](../app/Services/PlayerCategoryChangeService.php#L21)** — Programa o aplica un cambio manual de categoría permanente. Si la fecha efectiva ya llegó, se aplica inmediatamente. Si es futura, queda pendiente hasta esa fecha.
  Firma: `public static function scheduleManualChange( Player $player, Category $newCategory, CarbonInterface|string $effectiveDate, string $reason, ?string $notes = null ): PlayerCategoryHistory`
- **[applyChange](../app/Services/PlayerCategoryChangeService.php#L120)** — Aplica un cambio manual pendiente.
  Firma: `public static function applyChange( PlayerCategoryHistory $history ): void`
- **[applyDueChanges](../app/Services/PlayerCategoryChangeService.php#L189)** — Aplica todos los cambios manuales cuya fecha efectiva ya llegó. Cada cambio se procesa en su propia transacción para que el error de un jugador no impida aplicar los restantes.
  Firma: `public static function applyDueChanges(): int`

### Services/PlayerCategoryHistoryService.php

[Abrir archivo completo](../app/Services/PlayerCategoryHistoryService.php)

- **[recordAffiliationCategory](../app/Services/PlayerCategoryHistoryService.php#L19)** — Registrar categoría por afiliación inicial.
  Firma: `public function recordAffiliationCategory( Player $player, Category $category, ?string $notes = null ): void`
- **[recordTournamentCategory](../app/Services/PlayerCategoryHistoryService.php#L42)** — Registrar categoría del jugador en un torneo.
  Firma: `public function recordTournamentCategory( Player $player, Category $category, Tournament $tournament ): void`
- **[recordRankingCategory](../app/Services/PlayerCategoryHistoryService.php#L65)** — Registrar categoría asignada por ranking antiguo.
  Firma: `public function recordRankingCategory( Player $player, Category $category, Ranking5Quillas $ranking ): void`
- **[recordManualCategoryChange](../app/Services/PlayerCategoryHistoryService.php#L90)** — Crear un cambio manual de categoría. Puede quedar pendiente si la fecha efectiva es futura.
  Firma: `public function recordManualCategoryChange( Player $player, Category $previousCategory, Category $newCategory, CarbonInterface|string $effectiveDate, string $reason, ?string $notes = null, bool $applied = false ): PlayerCategoryHistory`
- **[recordTemporaryRankingChange](../app/Services/PlayerCategoryHistoryService.php#L120)** — Registrar cambio temporal producido por el Ranking General.
  Firma: `public function recordTemporaryRankingChange( Player $player, Category $previousCategory, Category $newCategory, int $season, CarbonInterface|string|null $effectiveDate = null, ?string $reason = null ): void`
- **[recordAffiliationChange](../app/Services/PlayerCategoryHistoryService.php#L167)** — Registrar cambio permanente de categoría de afiliación.
  Firma: `public function recordAffiliationChange( Player $player, Category $previousCategory, Category $newCategory, int $season, CarbonInterface|string $effectiveDate, ?string $reason = null ): void`
- **[recordPendingSeasonPromotion](../app/Services/PlayerCategoryHistoryService.php#L214)** — Registrar o actualizar un ascenso de temporada todavía pendiente. Se crea cuando se determinan las promociones al cierre de la Etapa 4, aunque la categoría permanente recién cambie en la fecha efectiva.
  Firma: `public function recordPendingSeasonPromotion( Player $player, Category $previousCategory, Category $newCategory, int $season, CarbonInterface|string $effectiveDate, ?string $reason = null, ?string $notes = null ): PlayerCategoryHistory`
- **[markSeasonPromotionHistoryApplied](../app/Services/PlayerCategoryHistoryService.php#L268)** — Marcar como aplicado el mismo historial creado cuando se determinó el ascenso de temporada.
  Firma: `public function markSeasonPromotionHistoryApplied( Player $player, Category $previousCategory, Category $newCategory, int $season, CarbonInterface|string $effectiveDate, ?string $reason = null ): PlayerCategoryHistory`
- **[getEffectiveCategory](../app/Services/PlayerCategoryHistoryService.php#L320)** — Obtener la categoría efectiva vigente del jugador. Considera solamente movimientos que ya fueron aplicados. Puede devolver una categoría temporal de ranking (M/N) o una categoría permanente. Si el jugador todavía no tiene un historial aplicable, utiliza su categoría actual de afiliación.
  Firma: `public function getEffectiveCategory(Player $player): ?Category`
- **[getPermanentCategory](../app/Services/PlayerCategoryHistoryService.php#L348)** — Obtener la categoría permanente vigente del jugador. Master (M) y Nacional (N) son categorías temporales del Ranking General y no modifican players.category_id. Por lo tanto, la categoría permanente vigente es siempre la categoría de afiliación actualmente almacenada en Player.
  Firma: `public function getPermanentCategory(Player $player): ?Category`
- **[getPlayersWithEffectiveTemporaryRankingCategory](../app/Services/PlayerCategoryHistoryService.php#L365)** — Obtener los jugadores cuya última categoría efectiva vigente es una categoría temporal del Ranking General (M o N). Durante la carga parcial de una etapa el GeneralRanking puede cambiar, pero el historial permanece intacto hasta que todos los resultados estén completos. Por eso este conjunto representa el estado oficial anterior que debemos conservar para detectar correctamente salidas de M/N al finalizar la carga de una nueva etapa.
  Firma: `public function getPlayersWithEffectiveTemporaryRankingCategory(): Collection`
- **[createHistoryRecord](../app/Services/PlayerCategoryHistoryService.php#L400)** — Método centralizado para crear registros de historial.
  Firma: `private function createHistoryRecord( Player $player, Category $category, ?Category $previousCategory, string $source, string $changeType, ?int $season, CarbonInterface|string|null $effectiveDate, ?string $reason, ?Tournament $tournament, ?Ranking5Quillas $ranking, ?string $notes, bool $applied = true ): PlayerCategoryHistory`

### Services/PlayerCategoryPromotionService.php

[Abrir archivo completo](../app/Services/PlayerCategoryPromotionService.php)

- **[determineSeasonPromotions](../app/Services/PlayerCategoryPromotionService.php#L33)** — Determina los ascensos correspondientes al cierre de una temporada. Regla 1 - prioridad máxima: Si un jugador cuya categoría de afiliación es S, T o PR termina dentro de la zona temporal M/N, asciende directamente a Primera. Regla 2: Si no fue alcanzado por la regla 1, el RC1 de cada categoría inferior asciende un nivel: S  -> P T  -> S PR -> T El ascenso se determina al cierre de la temporada, pero se hace efectivo el 1 de enero del año siguiente.
  Firma: `public static function determineSeasonPromotions(int $season): int`
- **[syncPendingPromotionHistories](../app/Services/PlayerCategoryPromotionService.php#L289)** — Sincroniza en el historial de categorías las promociones pendientes que ya fueron determinadas para una temporada. Este método NO modifica la categoría actual del jugador. Solamente garantiza que cada promoción pendiente tenga su correspondiente registro de historial también pendiente. Es útil, entre otros casos, para promociones determinadas antes de incorporar el registro inmediato del historial.
  Firma: `public static function syncPendingPromotionHistories(int $season): int`
- **[applyDuePromotions](../app/Services/PlayerCategoryPromotionService.php#L364)** — Aplica las promociones cuya fecha efectiva ya llegó. Actualiza la categoría permanente del jugador y registra el cambio en el historial de categorías.
  Firma: `public static function applyDuePromotions(): int`

### Services/PointsAssignmentService.php

[Abrir archivo completo](../app/Services/PointsAssignmentService.php)

- **[__construct](../app/Services/PointsAssignmentService.php#L15)** — Inyecta las dependencias necesarias sin ejecutar operaciones de dominio.
  Firma: `public function __construct( private readonly TournamentScoringService $scoringService )`
- **[assignPoints](../app/Services/PointsAssignmentService.php#L19)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function assignPoints( TournamentRegistration $registration ): void`

### Services/Ranking5QuillasService.php

[Abrir archivo completo](../app/Services/Ranking5QuillasService.php)

- **[recalculate](../app/Services/Ranking5QuillasService.php#L20)** — Reconstruye el ranking usando los últimos torneos puntuables de la disciplina.
  Firma: `public function recalculate(): void`
- **[updatePlayerRanking](../app/Services/Ranking5QuillasService.php#L47)** — Calcula y guarda el total de un jugador para la ventana de torneos recibida.
  Firma: `private function updatePlayerRanking(Player $player, $tournaments): void`
- **[assignPositions](../app/Services/Ranking5QuillasService.php#L79)** — Numera el ranking resultante respetando puntos y criterios de desempate.
  Firma: `private function assignPositions(): void`

### Services/RankingService.php

[Abrir archivo completo](../app/Services/RankingService.php)

- **[getGeneralRanking](../app/Services/RankingService.php#L20)** — Devuelve la colección formateada para el ranking general.
  Firma: `public static function getGeneralRanking(?string $category = null, ?string $search = null): Collection`
- **[syncGeneralRanking](../app/Services/RankingService.php#L390)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `query`, `getGeneralRanking`, `parse`, `transaction`, `create`.
  Firma: `public static function syncGeneralRanking(): void`
- **[saveSeasonRanking](../app/Services/RankingService.php#L675)** — Guarda una fotografía del Ranking General al cierre de una temporada.
  Firma: `public static function saveSeasonRanking(int $season): void`

### Services/ThreeCushionRankingService.php

[Abrir archivo completo](../app/Services/ThreeCushionRankingService.php)

- **[isThreeCushion](../app/Services/ThreeCushionRankingService.php#L24)** — Reconoce la disciplina aun cuando instalaciones antiguas usen nombres distintos.
  Firma: `public static function isThreeCushion(?Discipline $discipline): bool`
- **[discipline](../app/Services/ThreeCushionRankingService.php#L39)** — Obtiene la disciplina configurada para tres bandas.
  Firma: `public function discipline(): ?Discipline`
- **[syncForResult](../app/Services/ThreeCushionRankingService.php#L47)** — Sincroniza el ranking afectado por un resultado de etapa.
  Firma: `public function syncForResult(ThreeCushionStageResult $result): void`
- **[syncForTournamentType](../app/Services/ThreeCushionRankingService.php#L64)** — Sincroniza las temporadas alcanzadas por un tipo de torneo.
  Firma: `public function syncForTournamentType(TournamentType $type): void`
- **[syncSeason](../app/Services/ThreeCushionRankingService.php#L81)** — Reconstruye una temporada y devuelve las filas de ranking persistidas.
  Firma: `public function syncSeason(int $season, ?int $disciplineId = null): Collection`
- **[totalStagesForCategory](../app/Services/ThreeCushionRankingService.php#L181)** — Cuenta las etapas puntuables disponibles para una categoría en la temporada.
  Firma: `private function totalStagesForCategory(Collection $tournaments, int $categoryId): int`

### Services/TournamentRegistrationPdfService.php

[Abrir archivo completo](../app/Services/TournamentRegistrationPdfService.php)

- **[fileName](../app/Services/TournamentRegistrationPdfService.php#L12)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `slug`.
  Firma: `public static function fileName(TournamentRegistration $record): string`
- **[pdf](../app/Services/TournamentRegistrationPdfService.php#L21)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `absolute`, `pagos`, `loadView`.
  Firma: `public static function pdf(TournamentRegistration $record)`
- **[generate](../app/Services/TournamentRegistrationPdfService.php#L59)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `fileName`, `inscripciones`, `pdf`.
  Firma: `public static function generate(TournamentRegistration $record): string`

### Services/TournamentRegulationAuditPdfService.php

[Abrir archivo completo](../app/Services/TournamentRegulationAuditPdfService.php)

- **[download](../app/Services/TournamentRegulationAuditPdfService.php#L23)** — Genera y transmite el PDF de una auditoría con su evidencia conservada.
  Firma: `public function download(TournamentRegulationAudit $audit): StreamedResponse`
- **[arrayValue](../app/Services/TournamentRegulationAuditPdfService.php#L89)** — Normaliza valores JSON o colecciones como arreglo para la plantilla.
  Firma: `private function arrayValue(mixed $value): array`

### Services/TournamentRegulationEvaluation.php

[Abrir archivo completo](../app/Services/TournamentRegulationEvaluation.php)

- **[__construct](../app/Services/TournamentRegulationEvaluation.php#L13)** — Inyecta las dependencias necesarias sin ejecutar operaciones de dominio.
  Firma: `public function __construct( public readonly array $conflicts, public readonly array $technicalDetails = [], )`
- **[passes](../app/Services/TournamentRegulationEvaluation.php#L21)** — Indica si la evaluación terminó sin conflictos reglamentarios.
  Firma: `public function passes(): bool`
- **[message](../app/Services/TournamentRegulationEvaluation.php#L29)** — Resume los conflictos en un mensaje adecuado para la interfaz.
  Firma: `public function message(): string`

### Services/TournamentRegulationService.php

[Abrir archivo completo](../app/Services/TournamentRegulationService.php)

- **[distanceVerificationTournaments](../app/Services/TournamentRegulationService.php#L20)** — Lista torneos cercanos que deben intervenir en la validación reglamentaria.
  Firma: `public function distanceVerificationTournaments(Tournament $candidate): Collection`
- **[evaluate](../app/Services/TournamentRegulationService.php#L77)** — Evalúa el torneo candidato y devuelve conflictos y evidencias.
  Firma: `public function evaluate(Tournament $candidate): TournamentRegulationEvaluation`
- **[clubCategoryQuotaConflicts](../app/Services/TournamentRegulationService.php#L211)** — Detecta conflictos de cupo por club y categoría.
  Firma: `private function clubCategoryQuotaConflicts( Tournament $candidate, ?TournamentRegulationSetting $setting, ): array`
- **[googleMapsUrl](../app/Services/TournamentRegulationService.php#L350)** — Construye el enlace de verificación de ruta entre sedes de dos torneos.
  Firma: `public function googleMapsUrl(Tournament $candidate, Tournament $existing): string`
- **[googleMapsUrlForClubs](../app/Services/TournamentRegulationService.php#L363)** — Construye el enlace de ruta entre dos clubes.
  Firma: `public function googleMapsUrlForClubs($origin, $destination): string`
- **[manualRouteData](../app/Services/TournamentRegulationService.php#L376)** — Recupera distancia y evidencia cargadas manualmente para un par de torneos.
  Firma: `private function manualRouteData(Tournament $candidate, Tournament $existing): array`
- **[fullAddress](../app/Services/TournamentRegulationService.php#L404)** — Compone una dirección utilizable a partir de los datos disponibles del club.
  Firma: `public function fullAddress($club): string`
- **[hasCompleteAddress](../app/Services/TournamentRegulationService.php#L419)** — Indica si el club dispone de datos suficientes para una ruta automática.
  Firma: `private function hasCompleteAddress($club): bool`
- **[settingApplies](../app/Services/TournamentRegulationService.php#L431)** — Determina si una configuración reglamentaria estaba vigente en una fecha.
  Firma: `private function settingApplies(TournamentRegulationSetting $setting, ?CarbonInterface $date): bool`
- **[sharedCategoryIds](../app/Services/TournamentRegulationService.php#L444)** — Obtiene las categorías comunes entre dos torneos.
  Firma: `private function sharedCategoryIds(Tournament $first, Tournament $second): array`
- **[conflict](../app/Services/TournamentRegulationService.php#L454)** — Normaliza la estructura de un conflicto reglamentario.
  Firma: `private function conflict( string $rule, Tournament $existing, array $categories, string $reason, array $route = [], ): array`

### Services/TournamentRegulationWorkflow.php

[Abrir archivo completo](../app/Services/TournamentRegulationWorkflow.php)

- **[__construct](../app/Services/TournamentRegulationWorkflow.php#L22)** — Inyecta las dependencias necesarias sin ejecutar operaciones de dominio.
  Firma: `public function __construct(private readonly TournamentRegulationService $regulations)`
- **[shouldRevalidateUpdate](../app/Services/TournamentRegulationWorkflow.php#L27)** — Detecta si una edición modifica campos que obligan a reevaluar el reglamento.
  Firma: `public function shouldRevalidateUpdate(array $data, Tournament $record): bool`
- **[validate](../app/Services/TournamentRegulationWorkflow.php#L54)** — Valida una creación o edición y procesa el override autorizado cuando existe.
  Firma: `public function validate(array $data, User $user, string $operation, ?Tournament $record = null): array`
- **[audit](../app/Services/TournamentRegulationWorkflow.php#L116)** — Persiste la decisión reglamentaria con instantánea y evidencia.
  Firma: `public function audit( ?Tournament $tournament, User $user, string $operation, string $result, bool $overridden, ?string $overrideReason, array $snapshot, TournamentRegulationEvaluation $evaluation, ): TournamentRegulationAudit`
- **[preserveEvidence](../app/Services/TournamentRegulationWorkflow.php#L148)** — Copia evidencias a una ubicación persistente antes de guardar la auditoría.
  Firma: `private function preserveEvidence(array $snapshot, array $conflicts, array $technicalDetails): array`
- **[collectEvidencePaths](../app/Services/TournamentRegulationWorkflow.php#L181)** — Recorre datos anidados y reúne rutas de archivos de evidencia.
  Firma: `private function collectEvidencePaths(array $data, $paths): void`
- **[replaceEvidencePaths](../app/Services/TournamentRegulationWorkflow.php#L197)** — Sustituye rutas temporales por las copias persistidas.
  Firma: `private function replaceEvidencePaths(array $data, array $pathMap): array`
- **[snapshot](../app/Services/TournamentRegulationWorkflow.php#L213)** — Normaliza los datos relevantes que se conservarán en la auditoría.
  Firma: `private function snapshot(array $data): array`
- **[dateValue](../app/Services/TournamentRegulationWorkflow.php#L225)** — Convierte una fecha opcional a una representación estable.
  Firma: `private function dateValue(mixed $value): ?string`
- **[nullableInteger](../app/Services/TournamentRegulationWorkflow.php#L241)** — Normaliza un valor opcional como entero o nulo.
  Firma: `private function nullableInteger(mixed $value): ?int`

### Services/TournamentScoringService.php

[Abrir archivo completo](../app/Services/TournamentScoringService.php)

- **[assignBatch](../app/Services/TournamentScoringService.php#L29)** — Asigna una misma posición o resultado a varias inscripciones. Todos los cambios se guardan dentro de una única transacción. Si una inscripción no pertenece al torneo, está repetida o alguna regla falla, no se modifica ningún registro.
  Firma: `public function assignBatch( Tournament $tournament, array $items, ?int $tournamentInstanceId = null, ?string $resultCode = null, ): Collection`
- **[getRules](../app/Services/TournamentScoringService.php#L135)** — Obtiene las reglas aplicables al torneo. La tabla del tipo de torneo es la fuente única de puntuaciones.
  Firma: `public function getRules(Tournament $tournament): array`
- **[usesOfficialInstances](../app/Services/TournamentScoringService.php#L158)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar.
  Firma: `public function usesOfficialInstances(Tournament $tournament): bool`
- **[synchronizeTypeAssignments](../app/Services/TournamentScoringService.php#L166)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `transaction`, `query`, `absencePenaltyForRule`, `hasTable`, `syncGeneralRanking`.
  Firma: `public function synchronizeTypeAssignments(TournamentType $type): void`
- **[findRuleByCode](../app/Services/TournamentScoringService.php#L246)** — Busca una regla mediante su código propio. Se utiliza principalmente para tipos estadísticos que no afectan al Ranking General.
  Firma: `public function findRuleByCode( Tournament $tournament, string $resultCode ): ?array`
- **[findRuleByTournamentInstance](../app/Services/TournamentScoringService.php#L269)** — Busca una regla vinculada con una posición oficial. Se utiliza para CAB y para cualquier otro tipo que en el futuro afecte al Ranking General.
  Firma: `public function findRuleByTournamentInstance( Tournament $tournament, TournamentInstance $instance ): ?array`
- **[assignByCode](../app/Services/TournamentScoringService.php#L300)** — Asigna un resultado propio del tipo de torneo. Se utiliza para tipos estadísticos con resultados como: CHAMPION, RUNNER_UP, SEMIFINAL, etc.
  Firma: `public function assignByCode( TournamentRegistration $registration, string $resultCode ): TournamentRegistration`
- **[assignByTournamentInstance](../app/Services/TournamentScoringService.php#L330)** — Asigna una posición oficial vinculada con tournament_instances. Este método conserva la estructura utilizada actualmente por el Ranking General.
  Firma: `public function assignByTournamentInstance( TournamentRegistration $registration, int $tournamentInstanceId ): TournamentRegistration`
- **[getTournament](../app/Services/TournamentScoringService.php#L367)** — Obtiene el torneo de la inscripción.
  Firma: `private function getTournament( TournamentRegistration $registration ): Tournament`
- **[validateTournamentType](../app/Services/TournamentScoringService.php#L386)** — Verifica que el tipo pueda calcular puntos.
  Firma: `private function validateTournamentType( Tournament $tournament ): void`
- **[absencePenaltyForRule](../app/Services/TournamentScoringService.php#L419)** — Guarda una copia histórica del resultado y sus puntos.
  Firma: `public function absencePenaltyForRule(TournamentRegistration $registration, array $rule): float`
- **[applyRule](../app/Services/TournamentScoringService.php#L433)** — Sin contrato descriptivo en PHPDoc. Revisar el cuerpo antes de reutilizar o modificar. Referencias detectadas: `withMessages`, `transaction`, `absencePenaltyForRule`, `usesOfficialInstances`, `hasTable`.
  Firma: `private function applyRule( TournamentRegistration $registration, array $rule, ?TournamentInstance $instance = null ): TournamentRegistration`
