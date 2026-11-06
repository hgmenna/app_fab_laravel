# Mapa de archivos por funcionalidad

Actualizado el 6 de noviembre de 2026 a partir de los archivos reales del repositorio. Todos los enlaces son relativos y se pueden abrir directamente en GitHub. No se modificó código ejecutable para producir este catálogo.

Consultar primero la [guía de mantenimiento](guia-mantenimiento.md). La [referencia de métodos y acciones](referencia-metodos.md) permite buscar un nombre de función o identificador de acción.

## Cómo interpretar un recurso administrativo

`Resource.php` registra el módulo y sus páginas. `Schemas` define campos, opciones y validaciones del formulario; `Tables` define columnas, filtros y acciones del listado; `Pages` contiene páginas y acciones; `RelationManagers` administra registros asociados; `Widgets` dibuja indicadores. Cuando el recurso no tiene esas carpetas, su configuración puede estar en la propia clase Resource. No todas las pantallas tienen un Blade exclusivo.

## Órdenes de transferencia

| Archivo | Ubicación y responsabilidad |
|---|---|
| [BankTransferOrderResource.php](../app/Filament/Resources/BankTransferOrders/BankTransferOrderResource.php) | `BankTransferOrderResource.php` · Registro y configuración del módulo |
| [EditBankTransferOrder.php](../app/Filament/Resources/BankTransferOrders/Pages/EditBankTransferOrder.php) | `Pages/EditBankTransferOrder.php` · Página o acciones de la pantalla |
| [ListBankTransferOrders.php](../app/Filament/Resources/BankTransferOrders/Pages/ListBankTransferOrders.php) | `Pages/ListBankTransferOrders.php` · Página o acciones de la pantalla |

## Configuración bancaria

| Archivo | Ubicación y responsabilidad |
|---|---|
| [BankTransferSettingResource.php](../app/Filament/Resources/BankTransferSettings/BankTransferSettingResource.php) | `BankTransferSettingResource.php` · Registro y configuración del módulo |
| [ListBankTransferSettings.php](../app/Filament/Resources/BankTransferSettings/Pages/ListBankTransferSettings.php) | `Pages/ListBankTransferSettings.php` · Página o acciones de la pantalla |

## Categorías

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CategoryResource.php](../app/Filament/Resources/Categories/CategoryResource.php) | `CategoryResource.php` · Registro y configuración del módulo |
| [CreateCategory.php](../app/Filament/Resources/Categories/Pages/CreateCategory.php) | `Pages/CreateCategory.php` · Página o acciones de la pantalla |
| [EditCategory.php](../app/Filament/Resources/Categories/Pages/EditCategory.php) | `Pages/EditCategory.php` · Página o acciones de la pantalla |
| [ListCategories.php](../app/Filament/Resources/Categories/Pages/ListCategories.php) | `Pages/ListCategories.php` · Página o acciones de la pantalla |
| [CategoryForm.php](../app/Filament/Resources/Categories/Schemas/CategoryForm.php) | `Schemas/CategoryForm.php` · Formulario y campos |
| [CategoriesTable.php](../app/Filament/Resources/Categories/Tables/CategoriesTable.php) | `Tables/CategoriesTable.php` · Listado columnas filtros y acciones |

## Localidades

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CityResource.php](../app/Filament/Resources/Cities/CityResource.php) | `CityResource.php` · Registro y configuración del módulo |
| [CreateCity.php](../app/Filament/Resources/Cities/Pages/CreateCity.php) | `Pages/CreateCity.php` · Página o acciones de la pantalla |
| [EditCity.php](../app/Filament/Resources/Cities/Pages/EditCity.php) | `Pages/EditCity.php` · Página o acciones de la pantalla |
| [ListCities.php](../app/Filament/Resources/Cities/Pages/ListCities.php) | `Pages/ListCities.php` · Página o acciones de la pantalla |
| [CityForm.php](../app/Filament/Resources/Cities/Schemas/CityForm.php) | `Schemas/CityForm.php` · Formulario y campos |
| [CitiesTable.php](../app/Filament/Resources/Cities/Tables/CitiesTable.php) | `Tables/CitiesTable.php` · Listado columnas filtros y acciones |

## Clubes y salas

| Archivo | Ubicación y responsabilidad |
|---|---|
| [ClubResource.php](../app/Filament/Resources/Clubs/ClubResource.php) | `ClubResource.php` · Registro y configuración del módulo |
| [CreateClub.php](../app/Filament/Resources/Clubs/Pages/CreateClub.php) | `Pages/CreateClub.php` · Página o acciones de la pantalla |
| [EditClub.php](../app/Filament/Resources/Clubs/Pages/EditClub.php) | `Pages/EditClub.php` · Página o acciones de la pantalla |
| [ListClubs.php](../app/Filament/Resources/Clubs/Pages/ListClubs.php) | `Pages/ListClubs.php` · Página o acciones de la pantalla |
| [ClubForm.php](../app/Filament/Resources/Clubs/Schemas/ClubForm.php) | `Schemas/ClubForm.php` · Formulario y campos |
| [ClubsTable.php](../app/Filament/Resources/Clubs/Tables/ClubsTable.php) | `Tables/ClubsTable.php` · Listado columnas filtros y acciones |

## Concerns

| Archivo | Ubicación y responsabilidad |
|---|---|
| [ScopesToUserDisciplines.php](../app/Filament/Resources/Concerns/ScopesToUserDisciplines.php) | `ScopesToUserDisciplines.php` · Registro y configuración del módulo |

## Países

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CountryResource.php](../app/Filament/Resources/Countries/CountryResource.php) | `CountryResource.php` · Registro y configuración del módulo |
| [CreateCountry.php](../app/Filament/Resources/Countries/Pages/CreateCountry.php) | `Pages/CreateCountry.php` · Página o acciones de la pantalla |
| [EditCountry.php](../app/Filament/Resources/Countries/Pages/EditCountry.php) | `Pages/EditCountry.php` · Página o acciones de la pantalla |
| [ListCountries.php](../app/Filament/Resources/Countries/Pages/ListCountries.php) | `Pages/ListCountries.php` · Página o acciones de la pantalla |
| [ViewCountry.php](../app/Filament/Resources/Countries/Pages/ViewCountry.php) | `Pages/ViewCountry.php` · Página o acciones de la pantalla |
| [CountryForm.php](../app/Filament/Resources/Countries/Schemas/CountryForm.php) | `Schemas/CountryForm.php` · Formulario y campos |
| [CountryInfolist.php](../app/Filament/Resources/Countries/Schemas/CountryInfolist.php) | `Schemas/CountryInfolist.php` · Formulario y campos |
| [CountriesTable.php](../app/Filament/Resources/Countries/Tables/CountriesTable.php) | `Tables/CountriesTable.php` · Listado columnas filtros y acciones |

## Modalidades

| Archivo | Ubicación y responsabilidad |
|---|---|
| [DisciplineModalityResource.php](../app/Filament/Resources/DisciplineModalities/DisciplineModalityResource.php) | `DisciplineModalityResource.php` · Registro y configuración del módulo |
| [CreateDisciplineModality.php](../app/Filament/Resources/DisciplineModalities/Pages/CreateDisciplineModality.php) | `Pages/CreateDisciplineModality.php` · Página o acciones de la pantalla |
| [EditDisciplineModality.php](../app/Filament/Resources/DisciplineModalities/Pages/EditDisciplineModality.php) | `Pages/EditDisciplineModality.php` · Página o acciones de la pantalla |
| [ListDisciplineModalities.php](../app/Filament/Resources/DisciplineModalities/Pages/ListDisciplineModalities.php) | `Pages/ListDisciplineModalities.php` · Página o acciones de la pantalla |

## Disciplinas

| Archivo | Ubicación y responsabilidad |
|---|---|
| [DisciplineResource.php](../app/Filament/Resources/Disciplines/DisciplineResource.php) | `DisciplineResource.php` · Registro y configuración del módulo |
| [CreateDiscipline.php](../app/Filament/Resources/Disciplines/Pages/CreateDiscipline.php) | `Pages/CreateDiscipline.php` · Página o acciones de la pantalla |
| [EditDiscipline.php](../app/Filament/Resources/Disciplines/Pages/EditDiscipline.php) | `Pages/EditDiscipline.php` · Página o acciones de la pantalla |
| [ListDisciplines.php](../app/Filament/Resources/Disciplines/Pages/ListDisciplines.php) | `Pages/ListDisciplines.php` · Página o acciones de la pantalla |
| [DisciplineForm.php](../app/Filament/Resources/Disciplines/Schemas/DisciplineForm.php) | `Schemas/DisciplineForm.php` · Formulario y campos |
| [DisciplinesTable.php](../app/Filament/Resources/Disciplines/Tables/DisciplinesTable.php) | `Tables/DisciplinesTable.php` · Listado columnas filtros y acciones |

## Federaciones

| Archivo | Ubicación y responsabilidad |
|---|---|
| [FederationResource.php](../app/Filament/Resources/Federations/FederationResource.php) | `FederationResource.php` · Registro y configuración del módulo |
| [CreateFederation.php](../app/Filament/Resources/Federations/Pages/CreateFederation.php) | `Pages/CreateFederation.php` · Página o acciones de la pantalla |
| [EditFederation.php](../app/Filament/Resources/Federations/Pages/EditFederation.php) | `Pages/EditFederation.php` · Página o acciones de la pantalla |
| [ListFederations.php](../app/Filament/Resources/Federations/Pages/ListFederations.php) | `Pages/ListFederations.php` · Página o acciones de la pantalla |
| [FederationForm.php](../app/Filament/Resources/Federations/Schemas/FederationForm.php) | `Schemas/FederationForm.php` · Formulario y campos |
| [FederationsTable.php](../app/Filament/Resources/Federations/Tables/FederationsTable.php) | `Tables/FederationsTable.php` · Listado columnas filtros y acciones |

## Cuentas de membresía

| Archivo | Ubicación y responsabilidad |
|---|---|
| [MembershipAccountResource.php](../app/Filament/Resources/MembershipAccounts/MembershipAccountResource.php) | `MembershipAccountResource.php` · Registro y configuración del módulo |
| [ListMembershipAccounts.php](../app/Filament/Resources/MembershipAccounts/Pages/ListMembershipAccounts.php) | `Pages/ListMembershipAccounts.php` · Página o acciones de la pantalla |

## Movimientos y pagos

| Archivo | Ubicación y responsabilidad |
|---|---|
| [MembershipPaymentResource.php](../app/Filament/Resources/MembershipPayments/MembershipPaymentResource.php) | `MembershipPaymentResource.php` · Registro y configuración del módulo |
| [EditMembershipPayment.php](../app/Filament/Resources/MembershipPayments/Pages/EditMembershipPayment.php) | `Pages/EditMembershipPayment.php` · Página o acciones de la pantalla |
| [ListMembershipPayments.php](../app/Filament/Resources/MembershipPayments/Pages/ListMembershipPayments.php) | `Pages/ListMembershipPayments.php` · Página o acciones de la pantalla |

## Membresías anuales

| Archivo | Ubicación y responsabilidad |
|---|---|
| [MembershipResource.php](../app/Filament/Resources/Memberships/MembershipResource.php) | `MembershipResource.php` · Registro y configuración del módulo |
| [CreateMembership.php](../app/Filament/Resources/Memberships/Pages/CreateMembership.php) | `Pages/CreateMembership.php` · Página o acciones de la pantalla |
| [EditMembership.php](../app/Filament/Resources/Memberships/Pages/EditMembership.php) | `Pages/EditMembership.php` · Página o acciones de la pantalla |
| [ListMemberships.php](../app/Filament/Resources/Memberships/Pages/ListMemberships.php) | `Pages/ListMemberships.php` · Página o acciones de la pantalla |
| [MembershipForm.php](../app/Filament/Resources/Memberships/Schemas/MembershipForm.php) | `Schemas/MembershipForm.php` · Formulario y campos |
| [MembershipsTable.php](../app/Filament/Resources/Memberships/Tables/MembershipsTable.php) | `Tables/MembershipsTable.php` · Listado columnas filtros y acciones |

## Jugadores y desempeño

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CategoryChangesReport.php](../app/Filament/Resources/Players/Pages/CategoryChangesReport.php) | `Pages/CategoryChangesReport.php` · Página o acciones de la pantalla |
| [CreatePlayer.php](../app/Filament/Resources/Players/Pages/CreatePlayer.php) | `Pages/CreatePlayer.php` · Página o acciones de la pantalla |
| [EditPlayer.php](../app/Filament/Resources/Players/Pages/EditPlayer.php) | `Pages/EditPlayer.php` · Página o acciones de la pantalla |
| [FilteredPlayerPerformance.php](../app/Filament/Resources/Players/Pages/FilteredPlayerPerformance.php) | `Pages/FilteredPlayerPerformance.php` · Página o acciones de la pantalla |
| [ListPlayers.php](../app/Filament/Resources/Players/Pages/ListPlayers.php) | `Pages/ListPlayers.php` · Página o acciones de la pantalla |
| [PlayerPerformance.php](../app/Filament/Resources/Players/Pages/PlayerPerformance.php) | `Pages/PlayerPerformance.php` · Página o acciones de la pantalla |
| [PlayerResource.php](../app/Filament/Resources/Players/PlayerResource.php) | `PlayerResource.php` · Registro y configuración del módulo |
| [CategoryHistoriesRelationManager.php](../app/Filament/Resources/Players/RelationManagers/CategoryHistoriesRelationManager.php) | `RelationManagers/CategoryHistoriesRelationManager.php` · Gestión de relaciones |
| [PlayerForm.php](../app/Filament/Resources/Players/Schemas/PlayerForm.php) | `Schemas/PlayerForm.php` · Formulario y campos |
| [PlayersTable.php](../app/Filament/Resources/Players/Tables/PlayersTable.php) | `Tables/PlayersTable.php` · Listado columnas filtros y acciones |
| [AppliesPlayerFilters.php](../app/Filament/Resources/Players/Traits/AppliesPlayerFilters.php) | `Traits/AppliesPlayerFilters.php` · Comportamiento reutilizable |
| [PlayerChart.php](../app/Filament/Resources/Players/Widgets/PlayerChart.php) | `Widgets/PlayerChart.php` · Indicador o componente |

## Provincias y regiones

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CreateState.php](../app/Filament/Resources/States/Pages/CreateState.php) | `Pages/CreateState.php` · Página o acciones de la pantalla |
| [EditState.php](../app/Filament/Resources/States/Pages/EditState.php) | `Pages/EditState.php` · Página o acciones de la pantalla |
| [ListStates.php](../app/Filament/Resources/States/Pages/ListStates.php) | `Pages/ListStates.php` · Página o acciones de la pantalla |
| [StateForm.php](../app/Filament/Resources/States/Schemas/StateForm.php) | `Schemas/StateForm.php` · Formulario y campos |
| [StateResource.php](../app/Filament/Resources/States/StateResource.php) | `StateResource.php` · Registro y configuración del módulo |
| [StatesTable.php](../app/Filament/Resources/States/Tables/StatesTable.php) | `Tables/StatesTable.php` · Listado columnas filtros y acciones |

## Ranking de tres bandas

| Archivo | Ubicación y responsabilidad |
|---|---|
| [ListThreeCushionRankings.php](../app/Filament/Resources/ThreeCushionRankings/Pages/ListThreeCushionRankings.php) | `Pages/ListThreeCushionRankings.php` · Página o acciones de la pantalla |
| [ThreeCushionRankingsTable.php](../app/Filament/Resources/ThreeCushionRankings/Tables/ThreeCushionRankingsTable.php) | `Tables/ThreeCushionRankingsTable.php` · Listado columnas filtros y acciones |
| [ThreeCushionRankingResource.php](../app/Filament/Resources/ThreeCushionRankings/ThreeCushionRankingResource.php) | `ThreeCushionRankingResource.php` · Registro y configuración del módulo |

## Instancias de puntuación

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CreateTournamentInstance.php](../app/Filament/Resources/TournamentInstances/Pages/CreateTournamentInstance.php) | `Pages/CreateTournamentInstance.php` · Página o acciones de la pantalla |
| [EditTournamentInstance.php](../app/Filament/Resources/TournamentInstances/Pages/EditTournamentInstance.php) | `Pages/EditTournamentInstance.php` · Página o acciones de la pantalla |
| [ListTournamentInstances.php](../app/Filament/Resources/TournamentInstances/Pages/ListTournamentInstances.php) | `Pages/ListTournamentInstances.php` · Página o acciones de la pantalla |
| [TournamentInstanceForm.php](../app/Filament/Resources/TournamentInstances/Schemas/TournamentInstanceForm.php) | `Schemas/TournamentInstanceForm.php` · Formulario y campos |
| [TournamentInstancesTable.php](../app/Filament/Resources/TournamentInstances/Tables/TournamentInstancesTable.php) | `Tables/TournamentInstancesTable.php` · Listado columnas filtros y acciones |
| [TournamentInstanceResource.php](../app/Filament/Resources/TournamentInstances/TournamentInstanceResource.php) | `TournamentInstanceResource.php` · Registro y configuración del módulo |

## Inscripciones

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CreateTournamentRegistration.php](../app/Filament/Resources/TournamentRegistrations/Pages/CreateTournamentRegistration.php) | `Pages/CreateTournamentRegistration.php` · Página o acciones de la pantalla |
| [EditTournamentRegistration.php](../app/Filament/Resources/TournamentRegistrations/Pages/EditTournamentRegistration.php) | `Pages/EditTournamentRegistration.php` · Página o acciones de la pantalla |
| [ListTournamentRegistrations.php](../app/Filament/Resources/TournamentRegistrations/Pages/ListTournamentRegistrations.php) | `Pages/ListTournamentRegistrations.php` · Página o acciones de la pantalla |
| [TournamentRegistrationForm.php](../app/Filament/Resources/TournamentRegistrations/Schemas/TournamentRegistrationForm.php) | `Schemas/TournamentRegistrationForm.php` · Formulario y campos |
| [TournamentRegistrationsTable.php](../app/Filament/Resources/TournamentRegistrations/Tables/TournamentRegistrationsTable.php) | `Tables/TournamentRegistrationsTable.php` · Listado columnas filtros y acciones |
| [TournamentRegistrationResource.php](../app/Filament/Resources/TournamentRegistrations/TournamentRegistrationResource.php) | `TournamentRegistrationResource.php` · Registro y configuración del módulo |
| [DetectsNestedContext.php](../app/Filament/Resources/TournamentRegistrations/Traits/DetectsNestedContext.php) | `Traits/DetectsNestedContext.php` · Comportamiento reutilizable |

## Auditorías reglamentarias

| Archivo | Ubicación y responsabilidad |
|---|---|
| [ListTournamentRegulationAudits.php](../app/Filament/Resources/TournamentRegulationAudits/Pages/ListTournamentRegulationAudits.php) | `Pages/ListTournamentRegulationAudits.php` · Página o acciones de la pantalla |
| [TournamentRegulationAuditsTable.php](../app/Filament/Resources/TournamentRegulationAudits/Tables/TournamentRegulationAuditsTable.php) | `Tables/TournamentRegulationAuditsTable.php` · Listado columnas filtros y acciones |
| [TournamentRegulationAuditResource.php](../app/Filament/Resources/TournamentRegulationAudits/TournamentRegulationAuditResource.php) | `TournamentRegulationAuditResource.php` · Registro y configuración del módulo |

## Reglamentos

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CreateTournamentRegulationSetting.php](../app/Filament/Resources/TournamentRegulationSettings/Pages/CreateTournamentRegulationSetting.php) | `Pages/CreateTournamentRegulationSetting.php` · Página o acciones de la pantalla |
| [EditTournamentRegulationSetting.php](../app/Filament/Resources/TournamentRegulationSettings/Pages/EditTournamentRegulationSetting.php) | `Pages/EditTournamentRegulationSetting.php` · Página o acciones de la pantalla |
| [ListTournamentRegulationSettings.php](../app/Filament/Resources/TournamentRegulationSettings/Pages/ListTournamentRegulationSettings.php) | `Pages/ListTournamentRegulationSettings.php` · Página o acciones de la pantalla |
| [TournamentRegulationSettingForm.php](../app/Filament/Resources/TournamentRegulationSettings/Schemas/TournamentRegulationSettingForm.php) | `Schemas/TournamentRegulationSettingForm.php` · Formulario y campos |
| [TournamentRegulationSettingsTable.php](../app/Filament/Resources/TournamentRegulationSettings/Tables/TournamentRegulationSettingsTable.php) | `Tables/TournamentRegulationSettingsTable.php` · Listado columnas filtros y acciones |
| [TournamentRegulationSettingResource.php](../app/Filament/Resources/TournamentRegulationSettings/TournamentRegulationSettingResource.php) | `TournamentRegulationSettingResource.php` · Registro y configuración del módulo |

## Torneos y organización

| Archivo | Ubicación y responsabilidad |
|---|---|
| [InteractsWithTournamentRegulationModal.php](../app/Filament/Resources/Tournaments/Pages/Concerns/InteractsWithTournamentRegulationModal.php) | `Pages/Concerns/InteractsWithTournamentRegulationModal.php` · Página o acciones de la pantalla |
| [CreateTournament.php](../app/Filament/Resources/Tournaments/Pages/CreateTournament.php) | `Pages/CreateTournament.php` · Página o acciones de la pantalla |
| [EditTournament.php](../app/Filament/Resources/Tournaments/Pages/EditTournament.php) | `Pages/EditTournament.php` · Página o acciones de la pantalla |
| [ListTournaments.php](../app/Filament/Resources/Tournaments/Pages/ListTournaments.php) | `Pages/ListTournaments.php` · Página o acciones de la pantalla |
| [ManageTournamentCompetition.php](../app/Filament/Resources/Tournaments/Pages/ManageTournamentCompetition.php) | `Pages/ManageTournamentCompetition.php` · Página o acciones de la pantalla |
| [ManageTournamentRegistrations.php](../app/Filament/Resources/Tournaments/Pages/ManageTournamentRegistrations.php) | `Pages/ManageTournamentRegistrations.php` · Página o acciones de la pantalla |
| [ViewRegistrations.php](../app/Filament/Resources/Tournaments/Pages/ViewRegistrations.php) | `Pages/ViewRegistrations.php` · Página o acciones de la pantalla |
| [RegistrationsRelationManager.php](../app/Filament/Resources/Tournaments/RelationManagers/RegistrationsRelationManager.php) | `RelationManagers/RegistrationsRelationManager.php` · Gestión de relaciones |
| [TournamentForm.php](../app/Filament/Resources/Tournaments/Schemas/TournamentForm.php) | `Schemas/TournamentForm.php` · Formulario y campos |
| [TournamentsTable.php](../app/Filament/Resources/Tournaments/Tables/TournamentsTable.php) | `Tables/TournamentsTable.php` · Listado columnas filtros y acciones |
| [TournamentResource.php](../app/Filament/Resources/Tournaments/TournamentResource.php) | `TournamentResource.php` · Registro y configuración del módulo |

## Tipos de torneo y tablas de puntuación

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CreateTournamentType.php](../app/Filament/Resources/TournamentTypes/Pages/CreateTournamentType.php) | `Pages/CreateTournamentType.php` · Página o acciones de la pantalla |
| [EditTournamentType.php](../app/Filament/Resources/TournamentTypes/Pages/EditTournamentType.php) | `Pages/EditTournamentType.php` · Página o acciones de la pantalla |
| [ListTournamentTypes.php](../app/Filament/Resources/TournamentTypes/Pages/ListTournamentTypes.php) | `Pages/ListTournamentTypes.php` · Página o acciones de la pantalla |
| [TournamentTypeForm.php](../app/Filament/Resources/TournamentTypes/Schemas/TournamentTypeForm.php) | `Schemas/TournamentTypeForm.php` · Formulario y campos |
| [TournamentTypesTable.php](../app/Filament/Resources/TournamentTypes/Tables/TournamentTypesTable.php) | `Tables/TournamentTypesTable.php` · Listado columnas filtros y acciones |
| [TournamentTypeResource.php](../app/Filament/Resources/TournamentTypes/TournamentTypeResource.php) | `TournamentTypeResource.php` · Registro y configuración del módulo |

## Usuarios

| Archivo | Ubicación y responsabilidad |
|---|---|
| [CreateUser.php](../app/Filament/Resources/Users/Pages/CreateUser.php) | `Pages/CreateUser.php` · Página o acciones de la pantalla |
| [EditUser.php](../app/Filament/Resources/Users/Pages/EditUser.php) | `Pages/EditUser.php` · Página o acciones de la pantalla |
| [ListUsers.php](../app/Filament/Resources/Users/Pages/ListUsers.php) | `Pages/ListUsers.php` · Página o acciones de la pantalla |
| [UserForm.php](../app/Filament/Resources/Users/Schemas/UserForm.php) | `Schemas/UserForm.php` · Formulario y campos |
| [UsersTable.php](../app/Filament/Resources/Users/Tables/UsersTable.php) | `Tables/UsersTable.php` · Listado columnas filtros y acciones |
| [UserResource.php](../app/Filament/Resources/Users/UserResource.php) | `UserResource.php` · Registro y configuración del módulo |

## Paneles y escritorio

- [Auth/Login.php](../app/Filament/Pages/Auth/Login.php)
- [Dashboard.php](../app/Filament/Pages/Dashboard.php)
- [FiveQuillasRanking.php](../app/Filament/Pages/FiveQuillasRanking.php)

## Indicadores del escritorio

- [DashboardHeaderWidget.php](../app/Filament/Widgets/DashboardHeaderWidget.php)
- [PlayersByClubChart.php](../app/Filament/Widgets/PlayersByClubChart.php)
- [RankingGeneralWidget.php](../app/Filament/Widgets/RankingGeneralWidget.php)
- [RankingsOverviewWidget.php](../app/Filament/Widgets/RankingsOverviewWidget.php)
- [SportsSummaryWidget.php](../app/Filament/Widgets/SportsSummaryWidget.php)
- [ThreeCushionRankingWidget.php](../app/Filament/Widgets/ThreeCushionRankingWidget.php)
- [UpcomingByDisciplineWidget.php](../app/Filament/Widgets/UpcomingByDisciplineWidget.php)
- [UpcomingTournamentsWidget.php](../app/Filament/Widgets/UpcomingTournamentsWidget.php)

## Servicios de negocio

- [AdminNotifier.php](../app/Services/AdminNotifier.php)
- [AnnualMembershipService.php](../app/Services/AnnualMembershipService.php)
- [BankTransferService.php](../app/Services/BankTransferService.php)
- [FiveQuillasCompetitionService.php](../app/Services/FiveQuillasCompetitionService.php)
- [PlayerCategoryChangeService.php](../app/Services/PlayerCategoryChangeService.php)
- [PlayerCategoryHistoryService.php](../app/Services/PlayerCategoryHistoryService.php)
- [PlayerCategoryPromotionService.php](../app/Services/PlayerCategoryPromotionService.php)
- [PointsAssignmentService.php](../app/Services/PointsAssignmentService.php)
- [Ranking5QuillasService.php](../app/Services/Ranking5QuillasService.php)
- [RankingService.php](../app/Services/RankingService.php)
- [ThreeCushionRankingService.php](../app/Services/ThreeCushionRankingService.php)
- [TournamentRegistrationPdfService.php](../app/Services/TournamentRegistrationPdfService.php)
- [TournamentRegulationAuditPdfService.php](../app/Services/TournamentRegulationAuditPdfService.php)
- [TournamentRegulationEvaluation.php](../app/Services/TournamentRegulationEvaluation.php)
- [TournamentRegulationService.php](../app/Services/TournamentRegulationService.php)
- [TournamentRegulationWorkflow.php](../app/Services/TournamentRegulationWorkflow.php)
- [TournamentScoringService.php](../app/Services/TournamentScoringService.php)

## Modelos y relaciones

- [BankTransferOrder.php](../app/Models/BankTransferOrder.php)
- [BankTransferOrderItem.php](../app/Models/BankTransferOrderItem.php)
- [BankTransferSetting.php](../app/Models/BankTransferSetting.php)
- [Category.php](../app/Models/Category.php)
- [City.php](../app/Models/City.php)
- [Club.php](../app/Models/Club.php)
- [Country.php](../app/Models/Country.php)
- [Discipline.php](../app/Models/Discipline.php)
- [DisciplineModality.php](../app/Models/DisciplineModality.php)
- [DisciplineUserRole.php](../app/Models/DisciplineUserRole.php)
- [Federation.php](../app/Models/Federation.php)
- [FiveQuillasCompetition.php](../app/Models/FiveQuillasCompetition.php)
- [FiveQuillasEntry.php](../app/Models/FiveQuillasEntry.php)
- [FiveQuillasGroup.php](../app/Models/FiveQuillasGroup.php)
- [FiveQuillasGroupEntry.php](../app/Models/FiveQuillasGroupEntry.php)
- [FiveQuillasMatch.php](../app/Models/FiveQuillasMatch.php)
- [FiveQuillasPhase.php](../app/Models/FiveQuillasPhase.php)
- [GeneralRanking.php](../app/Models/GeneralRanking.php)
- [Membership.php](../app/Models/Membership.php)
- [Payment.php](../app/Models/Payment.php)
- [Permission.php](../app/Models/Permission.php)
- [Player.php](../app/Models/Player.php)
- [PlayerCategoryHistory.php](../app/Models/PlayerCategoryHistory.php)
- [PlayerCategoryPromotion.php](../app/Models/PlayerCategoryPromotion.php)
- [PlayerMembership.php](../app/Models/PlayerMembership.php)
- [Ranking5Quillas.php](../app/Models/Ranking5Quillas.php)
- [Ranking5QuillasStage.php](../app/Models/Ranking5QuillasStage.php)
- [RankingHistory.php](../app/Models/RankingHistory.php)
- [RankingSpecialPosition.php](../app/Models/RankingSpecialPosition.php)
- [Role.php](../app/Models/Role.php)
- [State.php](../app/Models/State.php)
- [ThreeCushionRanking.php](../app/Models/ThreeCushionRanking.php)
- [ThreeCushionStageResult.php](../app/Models/ThreeCushionStageResult.php)
- [Tournament.php](../app/Models/Tournament.php)
- [TournamentCategoryPrice.php](../app/Models/TournamentCategoryPrice.php)
- [TournamentContact.php](../app/Models/TournamentContact.php)
- [TournamentInstance.php](../app/Models/TournamentInstance.php)
- [TournamentModality.php](../app/Models/TournamentModality.php)
- [TournamentRegistration.php](../app/Models/TournamentRegistration.php)
- [TournamentRegistrationParticipant.php](../app/Models/TournamentRegistrationParticipant.php)
- [TournamentRegulationAudit.php](../app/Models/TournamentRegulationAudit.php)
- [TournamentRegulationSetting.php](../app/Models/TournamentRegulationSetting.php)
- [TournamentSlot.php](../app/Models/TournamentSlot.php)
- [TournamentType.php](../app/Models/TournamentType.php)
- [User.php](../app/Models/User.php)

## Autorización por recurso

- [CategoryPolicy.php](../app/Policies/CategoryPolicy.php)
- [CityPolicy.php](../app/Policies/CityPolicy.php)
- [ClubPolicy.php](../app/Policies/ClubPolicy.php)
- [Concerns/ChecksDisciplinePermissions.php](../app/Policies/Concerns/ChecksDisciplinePermissions.php)
- [CountryPolicy.php](../app/Policies/CountryPolicy.php)
- [DisciplineModalityPolicy.php](../app/Policies/DisciplineModalityPolicy.php)
- [DisciplinePolicy.php](../app/Policies/DisciplinePolicy.php)
- [FederationPolicy.php](../app/Policies/FederationPolicy.php)
- [PlayerPolicy.php](../app/Policies/PlayerPolicy.php)
- [RolePolicy.php](../app/Policies/RolePolicy.php)
- [StatePolicy.php](../app/Policies/StatePolicy.php)
- [TournamentInstancePolicy.php](../app/Policies/TournamentInstancePolicy.php)
- [TournamentPolicy.php](../app/Policies/TournamentPolicy.php)
- [TournamentRegistrationPolicy.php](../app/Policies/TournamentRegistrationPolicy.php)
- [TournamentRegulationAuditPolicy.php](../app/Policies/TournamentRegulationAuditPolicy.php)
- [TournamentRegulationSettingPolicy.php](../app/Policies/TournamentRegulationSettingPolicy.php)
- [TournamentTypePolicy.php](../app/Policies/TournamentTypePolicy.php)
- [UserPolicy.php](../app/Policies/UserPolicy.php)

## Controladores públicos y documentos

- [BankTransferOrderPdfController.php](../app/Http/Controllers/BankTransferOrderPdfController.php)
- [Controller.php](../app/Http/Controllers/Controller.php)
- [FiveQuillasCompetitionBroadcastController.php](../app/Http/Controllers/FiveQuillasCompetitionBroadcastController.php)
- [FiveQuillasCompetitionPageController.php](../app/Http/Controllers/FiveQuillasCompetitionPageController.php)
- [FiveQuillasCompetitionPublicController.php](../app/Http/Controllers/FiveQuillasCompetitionPublicController.php)
- [FiveQuillasCompetitionSectionPdfController.php](../app/Http/Controllers/FiveQuillasCompetitionSectionPdfController.php)
- [FiveQuillasRankingController.php](../app/Http/Controllers/FiveQuillasRankingController.php)
- [FiveQuillasRankingPdfController.php](../app/Http/Controllers/FiveQuillasRankingPdfController.php)
- [MembershipAccountPdfController.php](../app/Http/Controllers/MembershipAccountPdfController.php)
- [MembershipEntityStatementPdfController.php](../app/Http/Controllers/MembershipEntityStatementPdfController.php)
- [MembershipPaymentPdfController.php](../app/Http/Controllers/MembershipPaymentPdfController.php)
- [PlayerPerformancePdfDownloadController.php](../app/Http/Controllers/PlayerPerformancePdfDownloadController.php)
- [ThreeCushionRankingController.php](../app/Http/Controllers/ThreeCushionRankingController.php)
- [TournamentRegistrationPdfController.php](../app/Http/Controllers/TournamentRegistrationPdfController.php)
- [UpcomingTournamentsController.php](../app/Http/Controllers/UpcomingTournamentsController.php)

## Comandos y mantenimiento programado

- [ApplyDueCategoryPromotions.php](../app/Console/Commands/ApplyDueCategoryPromotions.php)
- [ApplyDuePlayerCategoryChanges.php](../app/Console/Commands/ApplyDuePlayerCategoryChanges.php)
- [CloseSeasonRanking.php](../app/Console/Commands/CloseSeasonRanking.php)
- [DetermineCategoryPromotions.php](../app/Console/Commands/DetermineCategoryPromotions.php)
- [ExpireAnnualMemberships.php](../app/Console/Commands/ExpireAnnualMemberships.php)
- [ExpireBankTransferOrders.php](../app/Console/Commands/ExpireBankTransferOrders.php)
- [GeocodeClubs.php](../app/Console/Commands/GeocodeClubs.php)
- [ImportRankingHistoryCommand.php](../app/Console/Commands/ImportRankingHistoryCommand.php)
- [RecalculateRanking.php](../app/Console/Commands/RecalculateRanking.php)
- [SeedRankingCategoryBaseline.php](../app/Console/Commands/SeedRankingCategoryBaseline.php)
- [SyncTournamentScoringAssignments.php](../app/Console/Commands/SyncTournamentScoringAssignments.php)
- [VerifyClientConfiguration.php](../app/Console/Commands/VerifyClientConfiguration.php)

## Correos

- [MembershipRenewalConfirmation.php](../app/Mail/MembershipRenewalConfirmation.php)
- [TournamentRegistrationNotification.php](../app/Mail/TournamentRegistrationNotification.php)

## Notificaciones

- [SystemActivityEmailNotification.php](../app/Notifications/SystemActivityEmailNotification.php)

## Trabajos en cola

- [GeneratePlayerPerformancePdf.php](../app/Jobs/GeneratePlayerPerformancePdf.php)

## Acceso y middleware

- [AssignGuestUser.php](../app/Http/Middleware/AssignGuestUser.php)
- [DisableShieldAuthForGuestPanel.php](../app/Http/Middleware/DisableShieldAuthForGuestPanel.php)

## Proveedores y registro de paneles

- [AppServiceProvider.php](../app/Providers/AppServiceProvider.php)
- [Filament/AdminPanelProvider.php](../app/Providers/Filament/AdminPanelProvider.php)
- [Filament/GuestPanelProvider.php](../app/Providers/Filament/GuestPanelProvider.php)

## Ayudas reutilizables

- [Cliente.php](../app/Helpers/Cliente.php)
- [FabPath.php](../app/Helpers/FabPath.php)
- [PdfHelper.php](../app/Helpers/PdfHelper.php)

## Formularios y vistas administrativas

- [brand.blade.php](../resources/views/filament/brand.blade.php)
- [clubs/partials/players-table.blade.php](../resources/views/filament/clubs/partials/players-table.blade.php)
- [clubs/players-inline-table.blade.php](../resources/views/filament/clubs/players-inline-table.blade.php)
- [dev-footer.blade.php](../resources/views/filament/dev-footer.blade.php)
- [forms/components/player-camera-capture.blade.php](../resources/views/filament/forms/components/player-camera-capture.blade.php)
- [membership-account-history.blade.php](../resources/views/filament/membership-account-history.blade.php)
- [resources/players/pages/category-changes-report.blade.php](../resources/views/filament/resources/players/pages/category-changes-report.blade.php)
- [resources/players/pages/filtered-player-performance.blade.php](../resources/views/filament/resources/players/pages/filtered-player-performance.blade.php)
- [resources/players/pages/player-performance.blade.php](../resources/views/filament/resources/players/pages/player-performance.blade.php)
- [resources/tournaments/pages/manage-tournament-competition.blade.php](../resources/views/filament/resources/tournaments/pages/manage-tournament-competition.blade.php)
- [resources/tournaments/pages/partials/competition-bracket.blade.php](../resources/views/filament/resources/tournaments/pages/partials/competition-bracket.blade.php)
- [resources/tournaments/pages/partials/competition-qualified-action.blade.php](../resources/views/filament/resources/tournaments/pages/partials/competition-qualified-action.blade.php)
- [resources/tournaments/pages/partials/competition-section-tools.blade.php](../resources/views/filament/resources/tournaments/pages/partials/competition-section-tools.blade.php)
- [resources/tournaments/pages/view-registrations.blade.php](../resources/views/filament/resources/tournaments/pages/view-registrations.blade.php)
- [tables/columns/three-cushion-stage-details.blade.php](../resources/views/filament/tables/columns/three-cushion-stage-details.blade.php)
- [tables/columns/tournament-categories.blade.php](../resources/views/filament/tables/columns/tournament-categories.blade.php)
- [tables/columns/tournament-conditions.blade.php](../resources/views/filament/tables/columns/tournament-conditions.blade.php)
- [tables/columns/tournament-dates.blade.php](../resources/views/filament/tables/columns/tournament-dates.blade.php)
- [tables/columns/tournament-registration-dates.blade.php](../resources/views/filament/tables/columns/tournament-registration-dates.blade.php)
- [tables/columns/tournament-registration-options.blade.php](../resources/views/filament/tables/columns/tournament-registration-options.blade.php)
- [tables/columns/tournament-sport-details.blade.php](../resources/views/filament/tables/columns/tournament-sport-details.blade.php)
- [tables/registrations-table.blade.php](../resources/views/filament/tables/registrations-table.blade.php)
- [tourments/partials/jugadores-por-horario.blade.php](../resources/views/filament/tourments/partials/jugadores-por-horario.blade.php)
- [widgets/dashboard-header-widget.blade.php](../resources/views/filament/widgets/dashboard-header-widget.blade.php)
- [widgets/rankings-overview-widget.blade.php](../resources/views/filament/widgets/rankings-overview-widget.blade.php)
- [widgets/registrations-tabs-widget.blade.php](../resources/views/filament/widgets/registrations-tabs-widget.blade.php)
- [widgets/upcoming-by-discipline-widget.blade.php](../resources/views/filament/widgets/upcoming-by-discipline-widget.blade.php)

## Página pública y transmisión

- [five-quillas-competition-broadcast.blade.php](../resources/views/public/five-quillas-competition-broadcast.blade.php)
- [five-quillas-competition-unavailable.blade.php](../resources/views/public/five-quillas-competition-unavailable.blade.php)
- [five-quillas-competition.blade.php](../resources/views/public/five-quillas-competition.blade.php)
- [partials/competition-availability.blade.php](../resources/views/public/partials/competition-availability.blade.php)

## Documentos PDF

- [bank-transfer-order.blade.php](../resources/views/pdf/bank-transfer-order.blade.php)
- [category-changes-report.blade.php](../resources/views/pdf/category-changes-report.blade.php)
- [filtered-player-performance.blade.php](../resources/views/pdf/filtered-player-performance.blade.php)
- [five-quillas-competition-section.blade.php](../resources/views/pdf/five-quillas-competition-section.blade.php)
- [generic-old.blade.php](../resources/views/pdf/generic-old.blade.php)
- [generic.blade.php](../resources/views/pdf/generic.blade.php)
- [inscription.blade.php](../resources/views/pdf/inscription.blade.php)
- [membership-account.blade.php](../resources/views/pdf/membership-account.blade.php)
- [membership-entity-statement.blade.php](../resources/views/pdf/membership-entity-statement.blade.php)
- [membership-payment.blade.php](../resources/views/pdf/membership-payment.blade.php)
- [membership-renewal.blade.php](../resources/views/pdf/membership-renewal.blade.php)
- [partials/fab-report-header-styles.blade.php](../resources/views/pdf/partials/fab-report-header-styles.blade.php)
- [partials/fab-report-header.blade.php](../resources/views/pdf/partials/fab-report-header.blade.php)
- [player-performance.blade.php](../resources/views/pdf/player-performance.blade.php)
- [ranking.blade.php](../resources/views/pdf/ranking.blade.php)
- [resumen-torneo.blade.php](../resources/views/pdf/resumen-torneo.blade.php)
- [three-cushion-ranking.blade.php](../resources/views/pdf/three-cushion-ranking.blade.php)
- [tournament-regulation-audit.blade.php](../resources/views/pdf/tournament-regulation-audit.blade.php)

## Plantillas de correo

- [membership-renewal.blade.php](../resources/views/emails/membership-renewal.blade.php)
- [tournament-registration.blade.php](../resources/views/emails/tournament-registration.blade.php)

## Scripts compartidos de vistas

- [competition-pdf-script.blade.php](../resources/views/partials/competition-pdf-script.blade.php)

## Estilos del sistema

- [app copy.css](../resources/css/app copy.css)
- [app.css](../resources/css/app.css)
- [filament/admin/filament-tables.css](../resources/css/filament/admin/filament-tables.css)
- [filament/admin/theme.css](../resources/css/filament/admin/theme.css)
- [filament-overrides.css](../resources/css/filament-overrides.css)

## Pruebas de comportamiento

- [AdminNotifierRecipientsTest.php](../tests/Feature/AdminNotifierRecipientsTest.php)
- [AnnualMembershipServiceTest.php](../tests/Feature/AnnualMembershipServiceTest.php)
- [ClientConfigurationTest.php](../tests/Feature/ClientConfigurationTest.php)
- [DisciplineScopedPermissionsTest.php](../tests/Feature/DisciplineScopedPermissionsTest.php)
- [ExampleTest.php](../tests/Feature/ExampleTest.php)
- [FilteredPlayerPerformanceTest.php](../tests/Feature/FilteredPlayerPerformanceTest.php)
- [FiveQuillasBracketByesTest.php](../tests/Feature/FiveQuillasBracketByesTest.php)
- [FiveQuillasBroadcastAvailabilityTest.php](../tests/Feature/FiveQuillasBroadcastAvailabilityTest.php)
- [FiveQuillasNationalBracketTest.php](../tests/Feature/FiveQuillasNationalBracketTest.php)
- [FiveQuillasPhaseScoringTest.php](../tests/Feature/FiveQuillasPhaseScoringTest.php)
- [PairTournamentRegistrationTest.php](../tests/Feature/PairTournamentRegistrationTest.php)
- [ThreeCushionRankingServiceTest.php](../tests/Feature/ThreeCushionRankingServiceTest.php)
- [TournamentCalendarStatusTest.php](../tests/Feature/TournamentCalendarStatusTest.php)
- [TournamentPublicationLogoTest.php](../tests/Feature/TournamentPublicationLogoTest.php)
- [TournamentRegulationServiceTest.php](../tests/Feature/TournamentRegulationServiceTest.php)
- [TournamentScoringServiceTest.php](../tests/Feature/TournamentScoringServiceTest.php)

## Pruebas unitarias

- [DisciplineAffiliationTest.php](../tests/Unit/DisciplineAffiliationTest.php)
- [ExampleTest.php](../tests/Unit/ExampleTest.php)
- [SystemActivityEmailNotificationTest.php](../tests/Unit/SystemActivityEmailNotificationTest.php)
- [TournamentRegistrationAvailabilityTest.php](../tests/Unit/TournamentRegistrationAvailabilityTest.php)
- [TournamentRegulationWorkflowTest.php](../tests/Unit/TournamentRegulationWorkflowTest.php)

## Pruebas JavaScript

- [wordpress-calendar.test.cjs](../tests/JavaScript/wordpress-calendar.test.cjs)

## Configuración rutas e integraciones

- [config/cliente.php](../config/cliente.php)
- [.env.cliente.example](../.env.cliente.example)
- [config/app.php](../config/app.php)
- [config/database.php](../config/database.php)
- [config/filesystems.php](../config/filesystems.php)
- [config/mail.php](../config/mail.php)
- [config/queue.php](../config/queue.php)
- [config/cache.php](../config/cache.php)
- [config/fab.php](../config/fab.php)
- [config/dompdf.php](../config/dompdf.php)
- [routes/web.php](../routes/web.php)
- [routes/console.php](../routes/console.php)
- [bootstrap/app.php](../bootstrap/app.php)
- [bootstrap/providers.php](../bootstrap/providers.php)
- [public/js/wordpress-calendar.js](../public/js/wordpress-calendar.js)
- [docs/wordpress/ranking-5-quillas-shortcode.php](../docs/wordpress/ranking-5-quillas-shortcode.php)
- [docs/wordpress/calendario-wpcode-9056.js](../docs/wordpress/calendario-wpcode-9056.js)
- [docs/wordpress/calendario-filtros.html](../docs/wordpress/calendario-filtros.html)

## Base de datos

Los cambios de estructura se encuentran en [database/migrations](../database/migrations). Los datos de ejemplo e históricos están en [database/seeders](../database/seeders) y las fábricas de prueba en [database/factories](../database/factories). No ejecutar seeders históricos de otra federación ni modificar migraciones ya aplicadas como método de corrección. Crear una nueva migración cuando sea necesaria una modificación del esquema.

## Archivos generados y externos

No editar `vendor`, `node_modules`, `storage/framework/views`, `bootstrap/cache` ni los assets publicados de terceros como fuente de una corrección. Buscar primero el archivo de aplicación que los genera. `public/js/wordpress-calendar.js` sí es código propio mantenido. `generic-old.blade.php` es un archivo de nombre histórico; comprobar las referencias antes de elegir una plantilla por su nombre.
