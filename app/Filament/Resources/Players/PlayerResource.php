<?php

namespace App\Filament\Resources\Players;

use App\Filament\Resources\Concerns\ScopesToUserDisciplines;
use App\Filament\Resources\Players\Pages\CategoryChangesReport;
use App\Filament\Resources\Players\Pages\CreatePlayer;
use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Filament\Resources\Players\Pages\FilteredPlayerPerformance;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Filament\Resources\Players\Pages\PlayerPerformance;
use App\Filament\Resources\Players\RelationManagers\CategoryHistoriesRelationManager;
use App\Filament\Resources\Players\Schemas\PlayerForm;
use App\Filament\Resources\Players\Tables\PlayersTable;
use App\Helpers\FabPath;
use App\Imports\PlayersImport;
use App\Models\Category;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\Federation;
use App\Models\GeneralRanking;
use App\Models\Membership;
use App\Models\Player;
use App\Services\AdminNotifier;
use App\Services\AnnualMembershipService;
use App\Services\PlayerCategoryChangeService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;
use UnitEnum;

class PlayerResource extends Resource
{
    use ScopesToUserDisciplines;

    protected static function applyAssignmentScope(Builder $query, $user): Builder
    {
        $assignments = $user->disciplineAssignments()
            ->where('is_active', true)
            ->whereHas('role.permissions', fn ($permissions) => $permissions->where('name', static::disciplineViewPermission()))
            ->get(['discipline_id', 'club_id']);

        return $query->where(function (Builder $scope) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $scope->orWhere(function (Builder $row) use ($assignment): void {
                    $row->where('discipline_id', $assignment->discipline_id);

                    if (filled($assignment->club_id)) {
                        $row->where('club_id', $assignment->club_id);
                    }
                });
            }
        });
    }

    protected static ?string $model = Player::class;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión Deportiva';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Jugadores';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PlayerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlayersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CategoryHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlayers::route('/'),
            'create' => CreatePlayer::route('/create'),
            'category-changes-report' => CategoryChangesReport::route('/category-changes-report'),
            'performance' => PlayerPerformance::route('/{record}/performance'),
            'performance-all' => FilteredPlayerPerformance::route('/performance/filtered/{report}'),
            'edit' => EditPlayer::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // Generacion de archivo Pdf
    public static function exportToPdf($records, string $title)
    {
        // Aumentar recursos para reportes pesados [2]
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $totalPlayers = $records->count();  // Total General

        // 1. DEFINICIÓN DE COLUMNAS (Sin columna CLUB porque se agrupa) [3]
        // Ancho total aproximado: 680px para Landscape
        $columns = [
            ['label' => 'APELLIDO',  'field' => 'last_name',        'width' => 180],
            ['label' => 'NOMBRE',    'field' => 'first_name',       'width' => 180],
            ['label' => 'PROVINCIA', 'field' => 'provincia_display', 'width' => 200],
            ['label' => 'CAT',       'field' => 'ranking_category', 'width' => 120],
        ];

        // 2. ORDENAMIENTO ALFABÉTICO (Directo sobre el modelo Player)
        $sortedRecords = $records->sortBy([
            ['last_name', 'asc'],
            ['first_name', 'asc'],
        ]);

        // 3. PROCESAMIENTO Y MAPEADO DE DATOS [1]
        $processed = $sortedRecords->map(function ($row) {
            // Búsqueda de Ranking usando los datos directos del Player [4, 5]
            $ranking = GeneralRanking::where('first_name', $row->first_name)
                ->where('last_name', $row->last_name)
                ->first();

            $categoryCode = $ranking?->category;
            $categoryName = Category::where('code', $categoryCode)->value('name');

            $category_display = $categoryName ?? ($row->category?->name ?? '-');

            return (object) [
                'last_name' => $row->last_name,
                'first_name' => $row->first_name,
                'provincia_display' => $row->club?->city?->state?->name ?? 'N/A',
                'ranking_category' => $category_display,
                'club_group' => $row->club->name ?? 'SIN INSTITUCIÓN', // Para agrupar
            ];
        });

        // 4. AGRUPACIÓN POR CLUB Y ORDEN ALFABÉTICO DE GRUPOS
        $grouped = $processed->groupBy('club_group')->sortKeys();

        // 5. CARGA DE VISTA Y CONFIGURACIÓN [3, 6]
        $pdf = Pdf::loadView('pdf.generic', [
            'title' => $title,
            'subtitle' => '',
            'date' => now()->format('d/m/Y'),
            'columns' => $columns,
            'groups' => $grouped, // Enviamos como grupos para repetir el TH [3]
            'totalGeneral' => $totalPlayers,
            'labelTotalGeneral' => 'Afiliados',
            'logo' => FabPath::logo(),
            'footer_image' => FabPath::footer(),
        ])->setPaper('a4', 'portrait'); // Orientación horizontal [6]

        // 6. DESCARGA MEDIANTE STREAM [6]
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $title.' - '.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Acción para programar o aplicar un cambio manual
     * de categoría permanente de afiliación.
     */
    public static function changeCategoryAction(): Action
    {
        return Action::make('changeCategory')
            ->label('Cambiar categoría')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(
                fn () => Auth::user()?->hasPermissionTo('EditField') ?? false
            )
            ->schema([
                Select::make('category_id')
                    ->label('Nueva categoría')
                    ->options(function (Player $record): array {
                        $temporaryCodes = config(
                            'ranking.temporary_ranking_categories',
                            ['M', 'N']
                        );

                        return Category::query()
                            ->where('discipline_id', $record->discipline_id)
                            ->whereNotIn('code', $temporaryCodes)
                            ->orderBy('order')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Category $category): array => [
                                $category->id => filled($category->code)
                                    ? "{$category->code} — {$category->name}"
                                    : $category->name,
                            ])
                            ->all();
                    })
                    ->required()
                    ->searchable()
                    ->preload(),

                DatePicker::make('effective_date')
                    ->label('Fecha efectiva')
                    ->default(today())
                    ->required()
                    ->native(false),

                Textarea::make('reason')
                    ->label('Motivo')
                    ->required()
                    ->rows(3)
                    ->maxLength(500),

                Textarea::make('notes')
                    ->label('Observaciones')
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->modalHeading('Cambio manual de categoría')
            ->modalDescription(
                'Si la fecha efectiva es futura, el cambio quedará pendiente '
                .'y se aplicará automáticamente cuando llegue esa fecha.'
            )
            ->modalSubmitActionLabel('Guardar cambio')
            ->action(function (Player $record, array $data): void {
                try {
                    $newCategory = Category::query()
                        ->whereKey($data['category_id'])
                        ->where('discipline_id', $record->discipline_id)
                        ->first();

                    if (! $newCategory) {
                        throw ValidationException::withMessages([
                            'category_id' => 'La categoría seleccionada no pertenece a la disciplina del jugador.',
                        ]);
                    }

                    $history = PlayerCategoryChangeService::scheduleManualChange(
                        player: $record,
                        newCategory: $newCategory,
                        effectiveDate: $data['effective_date'],
                        reason: $data['reason'],
                        notes: $data['notes'] ?? null
                    );

                    Notification::make()
                        ->title(
                            $history->applied_at
                                ? 'Categoría actualizada'
                                : 'Cambio de categoría programado'
                        )
                        ->body(
                            $history->applied_at
                                ? 'El cambio de categoría fue aplicado correctamente.'
                                : 'El cambio quedó pendiente hasta '
                                    .$history->effective_date->format('d/m/Y').'.'
                        )
                        ->success()
                        ->send();

                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('No se pudo realizar el cambio de categoría')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function viewCategoryHistoryAction(): Action
    {
        return Action::make('viewCategoryHistory')
            ->label('Historial de categorías')
            ->icon('heroicon-o-clock')
            ->color('info')
            ->modalHeading(
                fn (Player $record): string => 'Historial de categorías - '
                    .$record->last_name.', '.$record->first_name
            )
            ->modalWidth('7xl')
            ->modalContent(fn (Player $record) => view(
                'jugadores.table.historial-categorias',
                ['record' => $record]
            ))
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }

    // Accion para almacenar Pago Afilicacion del año corriente
    public static function payMembershipAction(): Action
    {
        $userAuth = Auth::user();

        return Action::make('payMembership')
            ->label('Afiliación')
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirmar Pago de Afiliacion')
            ->visible(fn () => $userAuth?->canGloballyOrInAnyDiscipline('PayMembership') ?? false)
            ->disabled(fn ($record) => $record?->is_enabled_to_compete)
            ->action(fn (Player $record) => static::processPayMembership($record));
    }

    public static function processPayMembership($records): void
    {
        $players = collect(is_iterable($records) ? $records : [$records]);
        $renewed = collect();

        foreach ($players->groupBy('discipline_id') as $disciplineId => $disciplinePlayers) {
            $membership = Membership::query()
                ->where('active', true)
                ->where('year', now()->year)
                ->where('discipline_id', $disciplineId)
                ->first();

            if (! $membership) {
                Notification::make()
                    ->title('Membresía anual sin configurar')
                    ->body('Configurá primero el año, importe y fecha de cierre de la disciplina.')
                    ->danger()
                    ->persistent()
                    ->send();

                continue;
            }

            foreach ($disciplinePlayers->groupBy(fn (Player $player) => $player->club_id ?? 0) as $clubId => $clubPlayers) {
                $renewed = $renewed->concat(app(AnnualMembershipService::class)->renew(
                    $membership,
                    $clubPlayers,
                    $clubId ? Club::find((int) $clubId) : null,
                ));
            }
        }

        if ($renewed->isNotEmpty()) {
            Notification::make()
                ->title('Orden de transferencia generada')
                ->body($renewed->count().' membresías quedaron pendientes de pago. Cargá el comprobante en Pagos por transferencia.')
                ->info()
                ->send();
        }
    }

    // Accion Exportar registros filtrados en archivo Pdf
    public static function exportarPdf(): Action
    {
        return Action::make('descargarPdf')
            ->label('Exportar PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function ($livewire) {
                // Obtenemos los registros filtrados desde el componente Livewire
                $records = $livewire->getFilteredTableQuery()->get();

                // Invocamos el método estático del Resource
                return PlayerResource::exportToPdf($records, 'Listado de Jugadores');
            }
            );
    }

    // Accion para importar Jugadores
    public static function importPlayers(): Action
    {
        $userAuth = Auth::user();

        return
            Action::make('importPlayers')
                ->label('Importar jugadores')
                ->visible(fn () => $userAuth?->name === 'super-admin')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->schema([
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    Excel::import(new PlayersImport, $data['file']);
                })
                ->modalHeading('Importar jugadores desde Excel')
                ->modalSubmitActionLabel('Importar');
    }

    public static function bulkCreateAction(): Action
    {
        return Action::make('bulkCreatePlayers')
            ->label('Alta masiva')
            ->icon('heroicon-o-user-group')
            ->color('success')
            ->visible(fn (): bool => Auth::user()?->canGloballyOrInAnyDiscipline('Create:Player') ?? false)
            ->modalHeading('Alta masiva de jugadores')
            ->modalDescription('Seleccioná la disciplina y el club comunes. Después agregá una fila por cada jugador.')
            ->modalSubmitActionLabel('Crear todos los jugadores')
            ->modalWidth('7xl')
            ->schema([
                Select::make('discipline_id')
                    ->label('Disciplina')
                    ->options(function (): array {
                        $query = Discipline::query()
                            ->where('active', true)
                            ->orderBy('name');

                        return Auth::user()?->scopeDisciplineQuery($query, 'Create:Player')
                            ->pluck('name', 'id')
                            ->all() ?? [];
                    })
                    ->default(fn () => Auth::user()?->defaultDisciplineId('Create:Player'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set) => $set('players', [[
                            'first_name' => null,
                            'last_name' => null,
                            'category_id' => null,
                    ]])),

                Select::make('club_id')
                    ->label('Club / sala')
                    ->options(fn (): array => Club::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => ! Discipline::query()
                        ->find($get('discipline_id'))?->allowsIndependentAffiliates())
                    ->placeholder('AFILIADOS INDEPENDIENTES')
                    ->helperText('En Pool puede dejarse vacío para crear afiliados sin club o sala.'),

                Repeater::make('players')
                    ->label('Jugadores a crear')
                    ->table([
                        TableColumn::make('Nombre')->markAsRequired(),
                        TableColumn::make('Apellido')->markAsRequired(),
                        TableColumn::make('Categoría')->markAsRequired(),
                    ])
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): string => mb_strtoupper(trim((string) $state))),
                        TextInput::make('last_name')
                            ->label('Apellido')
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): string => mb_strtoupper(trim((string) $state))),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get): array => Category::query()
                                ->where('discipline_id', $get('../../discipline_id'))
                                ->orderBy('order')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Category $category): array => [
                                    $category->id => filled($category->code)
                                        ? "{$category->code} — {$category->name}"
                                        : $category->name,
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->addActionLabel('Agregar jugador')
                    ->reorderable(false)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $disciplineId = (int) ($data['discipline_id'] ?? 0);
                $clubId = filled($data['club_id'] ?? null) ? (int) $data['club_id'] : null;
                $rows = collect($data['players'] ?? [])->values();
                $user = Auth::user();

                abort_unless(
                    $user?->canGloballyOrForDiscipline('Create:Player', $disciplineId),
                    403,
                );

                $discipline = Discipline::query()
                    ->whereKey($disciplineId)
                    ->where('active', true)
                    ->first();
                $clubExists = $clubId === null || Club::query()
                    ->whereKey($clubId)
                    ->where('is_active', true)
                    ->exists();
                $clubIsRequired = ! $discipline?->allowsIndependentAffiliates();

                if (! $discipline || ! $clubExists || ($clubIsRequired && $clubId === null) || $rows->isEmpty()) {
                    throw ValidationException::withMessages([
                        'players' => 'Seleccioná una disciplina y un club válidos y agregá al menos un jugador. En Pool el club puede quedar vacío.',
                    ]);
                }

                $categoryIds = $rows
                    ->pluck('category_id')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values();
                $validCategoryIds = Category::query()
                    ->where('discipline_id', $disciplineId)
                    ->whereKey($categoryIds)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id);

                if ($validCategoryIds->count() !== $categoryIds->count()) {
                    throw ValidationException::withMessages([
                        'players' => 'Una o más categorías no pertenecen a la disciplina seleccionada.',
                    ]);
                }

                $playerKey = fn (array $row): string => implode('|', [
                    mb_strtoupper(trim((string) ($row['last_name'] ?? ''))),
                    mb_strtoupper(trim((string) ($row['first_name'] ?? ''))),
                    $clubId,
                    (int) ($row['category_id'] ?? 0),
                ]);
                $categoryNames = Category::query()
                    ->whereKey($categoryIds)
                    ->pluck('name', 'id');
                $playerLabel = fn (array $row): string => sprintf(
                    '%s, %s — %s',
                    mb_strtoupper(trim((string) ($row['last_name'] ?? ''))),
                    mb_strtoupper(trim((string) ($row['first_name'] ?? ''))),
                    $categoryNames->get((int) ($row['category_id'] ?? 0), 'Sin categoría'),
                );
                $uniqueRows = collect();
                $skippedPlayers = collect();
                $seenKeys = [];

                foreach ($rows as $row) {
                    $key = $playerKey($row);

                    if (isset($seenKeys[$key])) {
                        $skippedPlayers->push($playerLabel($row).' (repetido en la tabla)');

                        continue;
                    }

                    $seenKeys[$key] = true;
                    $uniqueRows->push($row);
                }

                $submittedNames = $uniqueRows
                    ->mapWithKeys(fn (array $row): array => [implode('|', [
                        mb_strtoupper(trim((string) ($row['last_name'] ?? ''))),
                        mb_strtoupper(trim((string) ($row['first_name'] ?? ''))),
                        $clubId,
                        (int) ($row['category_id'] ?? 0),
                    ]) => true]);

                $existingPlayers = Player::query()
                    ->where(function (Builder $query) use ($clubId): void {
                        $clubId === null
                            ? $query->whereNull('club_id')
                            : $query->where('club_id', $clubId);
                    })
                    ->whereIn('category_id', $categoryIds)
                    ->whereIn('last_name', $rows
                        ->pluck('last_name')
                        ->map(fn ($lastName): string => mb_strtoupper(trim((string) $lastName)))
                        ->unique()
                        ->values())
                    ->get(['first_name', 'last_name', 'club_id', 'category_id'])
                    ->filter(fn (Player $player): bool => $submittedNames->has(implode('|', [
                        mb_strtoupper(trim($player->last_name)),
                        mb_strtoupper(trim($player->first_name)),
                        (int) $player->club_id,
                        (int) $player->category_id,
                    ])))
                    ->mapWithKeys(fn (Player $player): array => [implode('|', [
                        mb_strtoupper(trim($player->last_name)),
                        mb_strtoupper(trim($player->first_name)),
                        (int) $player->club_id,
                        (int) $player->category_id,
                    ]) => true]);

                $rowsToCreate = $uniqueRows
                    ->reject(function (array $row) use ($existingPlayers, $playerKey, $playerLabel, $skippedPlayers): bool {
                        if (! $existingPlayers->has($playerKey($row))) {
                            return false;
                        }

                        $skippedPlayers->push($playerLabel($row).' (ya existe)');

                        return true;
                    })
                    ->values();

                $createdPlayers = collect();

                DB::transaction(function () use ($rowsToCreate, $disciplineId, $clubId, $createdPlayers): void {
                    foreach ($rowsToCreate as $row) {
                        $createdPlayers->push(Player::create([
                            'first_name' => mb_strtoupper(trim((string) $row['first_name'])),
                            'last_name' => mb_strtoupper(trim((string) $row['last_name'])),
                            'category_id' => (int) $row['category_id'],
                            'discipline_id' => $disciplineId,
                            'club_id' => $clubId,
                            'is_active' => true,
                            'is_enabled_to_compete' => true,
                        ]));
                    }
                });

                if ($createdPlayers->isNotEmpty()) {
                    AdminNotifier::sendBulk(
                        $createdPlayers,
                        'dio de alta masivamente a',
                        ['last_name', 'first_name'],
                        'jugadores',
                    );

                    Notification::make()
                        ->success()
                        ->title($createdPlayers->count().' jugadores creados correctamente')
                        ->send();
                }

                if ($skippedPlayers->isNotEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title($createdPlayers->isEmpty()
                            ? 'No se crearon jugadores'
                            : $skippedPlayers->count().' jugadores duplicados omitidos')
                        ->body($skippedPlayers->unique()->sort()->join('; '))
                        ->persistent()
                        ->send();
                }
            })
            ->successNotification(null);
    }

    public static function configureAnnualMembershipAction(): Action
    {
        return Action::make('configureAnnualMembership')
            ->label('Configurar membresía anual')
            ->icon('heroicon-o-calendar-days')
            ->color('gray')
            ->visible(fn (): bool => Auth::user()?->hasRole('super-admin') ?? false)
            ->modalHeading('Configuración anual por disciplina')
            ->modalDescription('Definí el importe y la fecha de cierre. Desde esa fecha se inhabilitará automáticamente a quien no haya renovado.')
            ->schema([
                TextInput::make('year')->label('Año')->integer()->minValue(2020)->maxValue(2100)->default(now()->year)->required(),
                Select::make('discipline_id')
                    ->label('Disciplina')
                    ->options(fn (): array => Discipline::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->preload()->required(),
                TextInput::make('amount')->label('Importe anual')->numeric()->prefix('$')->minValue(0)->required(),
                DatePicker::make('due_date')
                    ->label('Fecha de cierre')
                    ->helperText('A partir de esta fecha se inhabilitan los jugadores que no renovaron.')
                    ->required(),
            ])
            ->action(function (array $data): void {
                app(AnnualMembershipService::class)->configure(
                    (int) $data['year'],
                    (int) $data['discipline_id'],
                    (float) $data['amount'],
                    (string) $data['due_date'],
                );

                Notification::make()->success()->title('Membresía anual configurada')->send();
            });
    }

    public static function renewClubMembershipsAction(): Action
    {
        return Action::make('renewClubMemberships')
            ->label('Renovación masiva')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('success')
            ->visible(fn (): bool => Auth::user()?->canGloballyOrInAnyDiscipline('RenewClubMemberships') ?? false)
            ->modalHeading('Renovación masiva de membresías')
            ->modalDescription('Seleccioná el pagador, el medio, el importe por jugador y las membresías. Admite pagos totales y parciales.')
            ->modalSubmitActionLabel('Registrar operación')
            ->modalWidth('7xl')
            ->schema([
                TextInput::make('year')
                    ->label('Año')->integer()->default(now()->year)->required()->live()
                    ->afterStateUpdated(function ($state, $set, $get): void {
                        $set('player_ids', []);
                        $set('payment_amount', Membership::query()
                            ->where('year', (int) $state)
                            ->where('discipline_id', (int) $get('discipline_id'))
                            ->value('amount'));
                    }),
                Select::make('discipline_id')
                    ->label('Disciplina')
                    ->options(function (): array {
                        $query = Discipline::query()->where('active', true)->orderBy('name');

                        return Auth::user()?->scopeDisciplineQuery($query, 'RenewClubMemberships')->pluck('name', 'id')->all() ?? [];
                    })
                    ->default(fn () => Auth::user()?->defaultDisciplineId('RenewClubMemberships'))
                    ->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function ($state, $set, $get): void {
                        $set('club_id', Auth::user()?->defaultClubId('RenewClubMemberships', $state));
                        $set('federation_id', null);
                        $set('player_ids', []);
                        $set('payment_amount', Membership::query()
                            ->where('year', (int) ($get('year') ?? now()->year))
                            ->where('discipline_id', (int) $state)
                            ->value('amount'));
                    }),
                Select::make('payer_type')
                    ->label('Responsable del pago')
                    ->options([
                        'club' => 'Club / sala',
                        'federation' => 'Federación provincial o subcomisión',
                        'players' => 'Jugadores seleccionados',
                    ])
                    ->default(fn (): string => Auth::user()?->defaultClubId('RenewClubMemberships') ? 'club' : 'players')
                    ->required()->live()
                    ->afterStateUpdated(function ($set): void {
                        $set('federation_id', null);
                        $set('player_ids', []);
                    }),
                Select::make('club_id')
                    ->label('Club / sala para este año')
                    ->options(function ($get): array {
                        $query = Club::query()->where('is_active', true)->orderBy('name');
                        $user = Auth::user();
                        $disciplineId = $get('discipline_id');

                        if ($user?->hasClubScopeForDiscipline($disciplineId, 'RenewClubMemberships')) {
                            $query->whereKey($user->allowedClubIds('RenewClubMemberships', $disciplineId) ?: [-1]);
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->default(fn ($get) => Auth::user()?->defaultClubId('RenewClubMemberships', $get('discipline_id')))
                    ->placeholder('AFILIADO INDEPENDIENTE')
                    ->helperText('La selección actualiza el club representado por el jugador durante este año.')
                    ->visible(fn ($get): bool => $get('payer_type') === 'club')
                    ->required(fn ($get): bool => $get('payer_type') === 'club')
                    ->searchable()->preload()->live()
                    ->afterStateUpdated(fn ($set) => $set('player_ids', [])),
                Select::make('federation_id')
                    ->label('Federación provincial / subcomisión pagadora')
                    ->options(fn (): array => Federation::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->visible(fn ($get): bool => $get('payer_type') === 'federation')
                    ->required(fn ($get): bool => $get('payer_type') === 'federation')
                    ->searchable()->preload()->live()
                    ->afterStateUpdated(fn ($set) => $set('player_ids', [])),
                Select::make('payment_mode')
                    ->label('Forma de acreditación')
                    ->options([
                        'transfer' => 'Generar orden de transferencia',
                        'manual' => 'Registrar pago ya recibido',
                    ])
                    ->default('transfer')->required()->live(),
                Select::make('manual_method')
                    ->label('Medio de pago recibido')
                    ->options(['cash' => 'Efectivo', 'bank_transfer' => 'Transferencia ya recibida', 'other' => 'Otro medio'])
                    ->visible(fn ($get): bool => $get('payment_mode') === 'manual')
                    ->required(fn ($get): bool => $get('payment_mode') === 'manual'),
                TextInput::make('payment_amount')
                    ->label('Importe a acreditar por jugador')
                    ->helperText('Puede ser el total de la membresía o un pago parcial. El sistema calculará el saldo restante.')
                    ->numeric()->prefix('$')->minValue(0.01)
                    ->required(),
                TextInput::make('payment_reference')
                    ->label('Referencia / número de recibo')
                    ->visible(fn ($get): bool => $get('payment_mode') === 'manual'),
                Textarea::make('payment_notes')
                    ->label('Observaciones del pago')
                    ->visible(fn ($get): bool => $get('payment_mode') === 'manual')
                    ->columnSpanFull(),
                CheckboxList::make('player_ids')
                    ->label('Jugadores pendientes de renovación')
                    ->options(function ($get): array {
                        $disciplineId = (int) ($get('discipline_id') ?? 0);
                        $year = (int) ($get('year') ?? now()->year);
                        if (! $disciplineId) {
                            return [];
                        }

                        $membershipIds = Membership::query()->where('year', $year)->where('discipline_id', $disciplineId)->pluck('id');
                        $query = Player::query()
                            ->with(['club', 'category'])
                            ->where('discipline_id', $disciplineId)
                            ->where('is_active', true)
                            ->whereDoesntHave('memberships', fn (Builder $memberships) => $memberships
                                ->whereIn('membership_id', $membershipIds)
                                ->where('status', 'paid'));

                        if ($get('payer_type') === 'federation' && $get('federation_id')) {
                            $federationId = (int) $get('federation_id');
                            $discipline = Discipline::find($disciplineId);
                            if ($discipline?->affiliation_mode === 'direct') {
                                $query->whereRaw((int) $discipline->direct_federation_id === $federationId ? '1 = 1' : '1 = 0');
                            } else {
                                $query->whereHas('club.city.state', fn (Builder $state) => $state->where('federation_id', $federationId));
                            }
                        }

                        return $query->orderBy('last_name')->orderBy('first_name')->get()
                            ->mapWithKeys(fn (Player $player): array => [
                                $player->id => $player->full_name.' — '.($player->club?->name ?? 'INDEPENDIENTE').' — '.($player->category?->code ?? 'Sin categoría'),
                            ])->all();
                    })
                    ->searchable()->bulkToggleable()->columns(2)->minItems(1)->required()->columnSpanFull(),
            ])
            ->action(function (array $data): void {
                $disciplineId = (int) $data['discipline_id'];
                $clubId = filled($data['club_id'] ?? null) ? (int) $data['club_id'] : null;
                $payerType = $data['payer_type'] ?? 'players';
                $federationId = filled($data['federation_id'] ?? null) ? (int) $data['federation_id'] : null;
                $user = Auth::user();

                abort_unless($user?->canAccessClubInDiscipline('RenewClubMemberships', $disciplineId, $clubId), 403);

                $membership = Membership::query()
                    ->where('year', (int) $data['year'])
                    ->where('discipline_id', $disciplineId)
                    ->where('active', true)
                    ->first();

                if (! $membership) {
                    throw ValidationException::withMessages(['year' => 'Primero debe configurarse la membresía anual de esta disciplina.']);
                }

                $discipline = Discipline::find($disciplineId);
                if ($payerType === 'club' && $clubId === null && ! $discipline?->allowsIndependentAffiliates()) {
                    throw ValidationException::withMessages(['club_id' => 'Seleccioná el club que representarán los jugadores.']);
                }

                $playersQuery = Player::query()
                    ->whereKey($data['player_ids'])
                    ->where('discipline_id', $disciplineId)
                    ->where('is_active', true);

                $players = $playersQuery->get();

                if ($players->count() !== count(array_unique(array_map('intval', $data['player_ids'])))) {
                    throw ValidationException::withMessages([
                        'player_ids' => 'Uno o más jugadores no pertenecen al alcance autorizado para este club y disciplina.',
                    ]);
                }

                if ($payerType === 'federation') {
                    $invalid = $players->first(function (Player $player) use ($discipline, $federationId): bool {
                        $player->loadMissing('club.city.state', 'discipline.directFederation');
                        return (int) $discipline?->federationForClub($player->club)?->id !== (int) $federationId;
                    });
                    if ($invalid) {
                        throw ValidationException::withMessages([
                            'player_ids' => "{$invalid->full_name} no pertenece a la federación pagadora seleccionada.",
                        ]);
                    }
                }

                $club = $clubId ? Club::findOrFail($clubId) : null;
                $payerId = $payerType === 'club' ? $clubId : ($payerType === 'federation' ? $federationId : null);
                $payerName = $payerType === 'club'
                    ? $club?->name
                    : ($payerType === 'federation' ? Federation::find($federationId)?->name : 'Jugadores seleccionados');
                $preserveClub = $payerType !== 'club';

                if (($data['payment_mode'] ?? 'transfer') === 'manual') {
                    $renewed = app(AnnualMembershipService::class)->renewManually(
                        $membership, $players, $club, $payerType, $payerId,
                        $data['manual_method'], (float) $data['payment_amount'], $data['payment_reference'] ?? null,
                        $data['payment_notes'] ?? null, $preserveClub,
                    );
                    $fullyPaid = $renewed->where('status', 'paid')->count();
                    $partial = $renewed->where('status', 'partial')->count();
                    Notification::make()->success()->title('Pagos registrados')
                        ->body("{$fullyPaid} membresías canceladas y {$partial} con saldo pendiente.")->send();
                    return;
                }

                $renewed = app(AnnualMembershipService::class)->renew(
                    $membership, $players, $club, $payerType, $payerId, $payerName, $preserveClub,
                    (float) $data['payment_amount'],
                );

                Notification::make()
                    ->info()
                    ->title('Orden de transferencia generada')
                    ->body($renewed->count().' membresías quedaron pendientes. Los jugadores se habilitarán cuando Tesorería apruebe el pago.')
                    ->send();
            });
    }
}
