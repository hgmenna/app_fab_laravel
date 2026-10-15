<?php

namespace App\Filament\Resources\TournamentRegistrations;

use App\Filament\Resources\Concerns\ScopesToUserDisciplines;
use App\Filament\Resources\TournamentRegistrations\Pages\CreateTournamentRegistration;
use App\Filament\Resources\TournamentRegistrations\Pages\EditTournamentRegistration;
use App\Filament\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Filament\Resources\TournamentRegistrations\Tables\TournamentRegistrationsTable;
use App\Helpers\FabPath;
use App\Models\GeneralRanking;
use App\Models\TournamentRegistration;
use App\Services\RankingService;
use App\Services\TournamentRegistrationPdfService;
use App\Services\TournamentScoringService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

class TournamentRegistrationResource extends Resource
{
    use ScopesToUserDisciplines;

    public static function shareByWhatsAppAction(): Action
    {
        return Action::make('shareRegistrationWhatsApp')
            ->label(new HtmlString(
                '<span style="display:inline-flex;align-items:center;gap:.45rem">'
                .'<svg aria-hidden="true" viewBox="0 0 32 32" width="18" height="18" fill="currentColor">'
                .'<path d="M16 3A13 13 0 0 0 4.78 22.54L3 29l6.62-1.74A13 13 0 1 0 16 3Zm0 23.62c-2.1 0-4.14-.62-5.88-1.78l-.42-.25-3.93 1.03 1.05-3.82-.27-.44A10.62 10.62 0 1 1 16 26.62Zm5.82-7.95c-.32-.16-1.88-.93-2.17-1.03-.29-.11-.5-.16-.71.16-.21.32-.82 1.03-1 1.24-.19.21-.37.24-.69.08-.32-.16-1.34-.49-2.55-1.58-.94-.84-1.58-1.88-1.76-2.2-.19-.32-.02-.49.14-.65.14-.14.32-.37.48-.56.16-.19.21-.32.32-.53.11-.21.05-.4-.03-.56-.08-.16-.71-1.72-.98-2.35-.26-.62-.52-.54-.71-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.11 1.09-1.11 2.65 0 1.56 1.14 3.07 1.3 3.28.16.21 2.24 3.42 5.43 4.8.76.33 1.35.52 1.81.67.76.24 1.46.21 2.01.13.61-.09 1.88-.77 2.14-1.51.26-.74.26-1.38.19-1.51-.08-.13-.29-.21-.61-.37Z"/>'
                .'</svg><span>Enviar constancia</span></span>',
            ))
            ->color('success')
            ->disabled(fn (TournamentRegistration $record): bool => self::whatsAppRecipient($record) === null)
            ->tooltip(fn (TournamentRegistration $record): string => self::whatsAppRecipient($record)
                ? 'Compartir manualmente por WhatsApp'
                : 'Los jugadores de esta inscripción no tienen teléfono cargado')
            ->url(fn (TournamentRegistration $record): string => self::registrationWhatsAppUrl($record))
            ->openUrlInNewTab();
    }

    public static function registrationWhatsAppUrl(TournamentRegistration $registration): string
    {
        $registration->loadMissing(['participants.player', 'tournament', 'tournamentModality.modality', 'slot']);
        $recipient = self::whatsAppRecipient($registration);

        if (! $recipient) {
            return '#';
        }

        $status = match ($registration->status) {
            'aprobado' => 'APROBADA',
            'denegado', 'rechazado' => 'RECHAZADA',
            default => 'PENDIENTE',
        };
        $pdfUrl = URL::temporarySignedRoute(
            'tournament-registrations.pdf',
            now()->addDays(7),
            ['registration' => $registration],
        );
        $message = implode("\n", [
            'Federación Argentina de Billar',
            '',
            "Inscripción {$status}",
            'Torneo: '.($registration->tournament?->name ?? 'Sin informar'),
            'Jugador/es: '.$registration->participant_names,
            'Modalidad: '.($registration->tournamentModality?->modality?->name ?? 'Sin modalidad'),
            'Horario: '.($registration->slot?->name ?? 'Sin asignar'),
            '',
            'Descargar constancia:',
            $pdfUrl,
        ]);

        return "https://wa.me/{$recipient}?text=".rawurlencode($message);
    }

    public static function whatsAppRecipient(TournamentRegistration $registration): ?string
    {
        $registration->loadMissing(['participants.player', 'player']);
        $phone = $registration->participants
            ->pluck('player.phone')
            ->first(fn ($value): bool => filled($value)) ?: $registration->player?->phone;
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (! $digits) {
            return null;
        }

        $digits = preg_replace('/^00/', '', $digits);
        if (str_starts_with($digits, '54')) {
            $nationalNumber = ltrim(substr($digits, 2), '0');

            return str_starts_with($nationalNumber, '9')
                ? '54'.$nationalNumber
                : '549'.$nationalNumber;
        }

        $digits = ltrim($digits, '0');
        if (strlen($digits) === 10) {
            $digits = '549'.$digits;
        }

        return $digits;
    }

    protected static ?string $model = TournamentRegistration::class;

    protected static function applyDisciplineScope(Builder $query, array $disciplineIds): Builder
    {
        return $query->whereHas(
            'tournament',
            fn (Builder $tournaments) => $tournaments->whereIn('discipline_id', $disciplineIds ?: [-1]),
        );
    }

    protected static function applyAssignmentScope(Builder $query, $user): Builder
    {
        $assignments = $user->disciplineAssignments()
            ->where('is_active', true)
            ->whereHas('role.permissions', fn ($permissions) => $permissions->where('name', static::disciplineViewPermission()))
            ->get(['discipline_id', 'club_id']);

        return $query->whereHas('tournament', function (Builder $tournaments) use ($assignments): void {
            $tournaments->where(function (Builder $scope) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $scope->orWhere(function (Builder $row) use ($assignment): void {
                        $row->where('discipline_id', $assignment->discipline_id);

                        if (filled($assignment->club_id)) {
                            $row->where('venue_type', 'club')->where('venue_id', $assignment->club_id);
                        }
                    });
                }
            });
        });
    }

    protected static function disciplineViewPermission(): string
    {
        return 'ViewAny:Tournament';
    }

    protected static string|UnitEnum|null $navigationGroup = 'Torneos';

    protected static ?string $navigationLabel = 'Inscripciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $title = 'Inscripciones';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TournamentRegistrationForm::configure($schema, request()->route('record'));
    }

    public static function table(Table $table): Table
    {
        return TournamentRegistrationsTable::configure($table, request()->route('record'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTournamentRegistrations::route('/'),
            'create' => CreateTournamentRegistration::route('/create'),
            'edit' => EditTournamentRegistration::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function exportRegistrationsToPdf($tournament, $slotId = null, $status = null)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // 1. Obtener inscripciones usando la relación correcta 'slot' definida en el modelo [3]
        // Se usa $tournament->registrations() asumiendo que el modelo Tournament tiene esta relación [1]
        $query = $tournament->registrations()
            ->with([
                'participants.player.club.city.state',
                'participants.player.category',
                'tournamentModality.modality',
                'slot',
            ]);

        // Aplicar filtro por horario si esta seleccionado
        if ($slotId) {
            $query->where('tournament_slot_id', $slotId);
        }

        // Aplicar filtro de status si esta activo
        if ($status) {
            $query->where('status', $status);
        }

        $registrations = $query->get();
        $totalGeneral = $registrations->count(); // Total de inscripciones
        $publicationLogo = $tournament->publicationLogoPath();
        $logo = filled($publicationLogo) ? FabPath::absolute($publicationLogo) : null;

        if (! $logo || ! is_file($logo)) {
            $institutionalLogo = FabPath::logo();
            $logo = is_file($institutionalLogo) ? $institutionalLogo : public_path('images/logo.png');
        }

        $footerImage = is_file(FabPath::footer()) ? FabPath::footer() : public_path('images/pie-pagina.png');

        // 2. Procesar datos y Ranking (basado en fuentes [4-6])
        $processed = $registrations->flatMap(function ($registration) {
            return $registration->participants->map(function ($participant) use ($registration) {
                $player = $participant->player;
                $generalRanking = GeneralRanking::where('player_id', $player?->id)->first();

                return (object) [
                    'last_name' => $player?->last_name ?? 'N/A',
                    'first_name' => $player?->first_name ?? 'N/A',
                    'club_display' => $player?->club?->name ?? 'N/A',
                    'provincia_display' => $player?->club?->city?->state?->name ?? 'N/A',
                    'ranking_category' => $generalRanking?->category ?? $player?->category?->code ?? '-',
                    'ranking_rg' => Str::limit($generalRanking?->RG ?? 9999, 4),
                    'slot_name' => implode(' · ', array_filter([
                        $registration->tournamentModality?->modality?->name,
                        $registration->slot?->name ?? 'Sin horario',
                    ])),
                ];
            });
        });

        // 3. Ordenar por RG Ascendente y Agrupar por Horario (Slot)
        $grouped = $processed->sortBy('ranking_rg')->groupBy('slot_name');

        $columns = [
            ['label' => 'APELLIDO', 'field' => 'last_name', 'width' => 120],
            ['label' => 'NOMBRE', 'field' => 'first_name', 'width' => 120],
            ['label' => 'CLUB', 'field' => 'club_display', 'width' => 220],
            ['label' => 'PROVINCIA', 'field' => 'provincia_display', 'width' => 150],
            ['label' => 'CAT', 'field' => 'ranking_category', 'width' => 35],
            ['label' => 'RG', 'field' => 'ranking_rg', 'width' => 35],
        ];

        // 4. Generar PDF con la vista genérica (asegúrate de que use el encabezado de tabla solicitado)
        $pdf = Pdf::loadView('pdf.generic', [
            'title' => $tournament->name,
            'subtitle' => $slotId ? 'Nomina de Jugadores por Horario' : 'Nomina de Jugadores Inscriptos',
            'date' => now()->format('d/m/Y'),
            'columns' => $columns,
            'groups' => $grouped,
            'totalGeneral' => $totalGeneral, // Pasamos el total de inscriptos
            'labelTotalGeneral' => 'Inscripciones',
            'logo' => $logo,
            'footer_image' => $footerImage,
            'system_name' => 'Sistema Administrativo Federación Argentina de Billar',
            'system_version' => config('app.version'),
            'developer_name' => 'Hernán Gabriel Menna',
            'developer_phone' => '+54 9 341 598 8191',
            'developer_email' => 'hgmenna@hotmail.com',
        ])->setPaper('a4', 'portrait');

        // Nombre dinamico para el pdf
        $fileName = $slotId
            ? "Inscripciones-{$tournament->name}-{$slotId}.pdf"
            : "Inscripciones-{$tournament->name}.pdf";

        return response()->streamDownload(fn () => print ($pdf->output()), $fileName);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // antes estaba false por ser nested
    }

    public static function isNested($livewire): bool
    {
        return $livewire instanceof \Filament\Resources\RelationManagers\RelationManager ||
            $livewire instanceof \Filament\Resources\Pages\ManageRelatedRecords;
    }

    public static function AsignInstanceAction(): Action
    {
        return Action::make('asignarInstancia')
            ->label('Asignar posición')
            ->authorize(
                fn (?TournamentRegistration $record): bool => Auth::user()?->canAccessTournament(
                    'AssignTournamentScore',
                    $record?->tournament,
                ) ?? false
            )
            ->visible(
                fn (?TournamentRegistration $record): bool => (Auth::user()?->canAccessTournament(
                    'AssignTournamentScore',
                    $record?->tournament,
                ) ?? false)
                    && $record?->tournament?->start_date < now()
            )
            ->disabled(
                fn (TournamentRegistration $record): bool => ($record->tournament?->start_date > now())
                    && (Auth::user()->name !== 'super-admin')
            )
            ->modalHeading('Asignar resultado y calcular puntos')
            ->modalSubmitActionLabel('Guardar resultado')
            ->fillForm(
                fn (TournamentRegistration $record): array => [
                    'tournament_instance_id' => $record->tournament_instance_id,

                    'result_code' => $record->result_code,

                    'penalty_points' => $record->penalty_points ?? 0,
                ]
            )
            ->form([
                /*
             * Los torneos que afectan al ranking continúan utilizando
             * tournament_instances y tournament_instance_id.
             */
                Select::make('tournament_instance_id')
                    ->label('Posición oficial')
                    ->options(function (
                        ?TournamentRegistration $record
                    ): array {
                        $tournament = $record?->tournament;

                        if (
                            ! $tournament
                            || ! app(TournamentScoringService::class)->usesOfficialInstances($tournament)
                        ) {
                            return [];
                        }

                        $rules = app(
                            TournamentScoringService::class
                        )->getRules($tournament);

                        return collect($rules)
                            ->filter(
                                fn (array $rule): bool => ! empty($rule['tournament_instance_id']
                                    ?? null)
                            )
                            ->mapWithKeys(function (array $rule): array {
                                $instanceId = (int)
                                $rule['tournament_instance_id'];

                                $description =
                                    $rule['description']
                                    ?? 'Sin descripción';

                                $points = $rule['points'] ?? 0;

                                return [
                                    $instanceId => "{$description} — {$points} puntos",
                                ];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->visible(
                        fn (?TournamentRegistration $record): bool => $record?->tournament
                            ? app(TournamentScoringService::class)->usesOfficialInstances($record->tournament)
                            : false
                    )
                    ->nullable(),

                /*
             * Los tipos que no afectan al ranking utilizan únicamente
             * los resultados definidos en su propio array.
             */
                Select::make('result_code')
                    ->label('Resultado')
                    ->options(function (
                        ?TournamentRegistration $record
                    ): array {
                        $tournament = $record?->tournament;

                        if (
                            ! $tournament
                            || app(TournamentScoringService::class)->usesOfficialInstances($tournament)
                        ) {
                            return [];
                        }

                        $rules = app(
                            TournamentScoringService::class
                        )->getRules($tournament);

                        return collect($rules)
                            ->filter(
                                fn (array $rule): bool => filled($rule['code'] ?? null)
                            )
                            ->mapWithKeys(function (array $rule): array {
                                $code = (string) $rule['code'];

                                $description =
                                    $rule['description']
                                    ?? 'Sin descripción';

                                $points = $rule['points'] ?? 0;

                                return [
                                    $code => "{$description} — {$points} puntos",
                                ];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->visible(
                        fn (?TournamentRegistration $record): bool => $record?->tournament
                            ? ! app(TournamentScoringService::class)->usesOfficialInstances($record->tournament)
                            : false
                    )
                    ->nullable(),

                TextInput::make('penalty_points')
                    ->label('Penalización')
                    ->numeric()
                    ->default(0)
                    ->helperText(
                        'Puntos a descontar por inasistencia en Master/1ra.'
                    ),
            ])
            ->action(function (
                array $data,
                TournamentRegistration $record
            ): void {
                $record->loadMissing('tournament.type');

                $record->penalty_points =
                    $data['penalty_points'] ?? 0;

                $service = app(
                    TournamentScoringService::class
                );

                if ($service->usesOfficialInstances($record->tournament)) {
                    $instanceId =
                        $data['tournament_instance_id'] ?? null;

                    /*
                 * Se permite quitar una posición asignada.
                 */
                    if (! $instanceId) {
                        $record->tournament_instance_id = null;
                        $record->result_code = null;
                        $record->result_description = null;
                        $record->result_instance_value = null;
                        $record->points = null;
                        $record->save();
                    } else {
                        /*
                     * Conserva tournament_instance_id para que el
                     * Ranking General funcione sin modificaciones.
                     */
                        $service->assignByTournamentInstance(
                            $record,
                            (int) $instanceId
                        );
                    }

                    /*
                 * El ranking se sincroniza únicamente si el tipo
                 * de torneo realmente lo afecta.
                 */
                    RankingService::syncGeneralRanking();

                    return;
                }

                $resultCode = $data['result_code'] ?? null;

                /*
             * Para torneos estadísticos también se permite quitar
             * un resultado previamente asignado.
             */
                if (! $resultCode) {
                    $record->tournament_instance_id = null;
                    $record->result_code = null;
                    $record->result_description = null;
                    $record->result_instance_value = null;
                    $record->points = null;
                    $record->save();

                    return;
                }

                /*
             * Los torneos estadísticos guardan puntos y resultado,
             * pero no ejecutan ninguna sincronización del ranking.
             */
                $service->assignByCode(
                    $record,
                    (string) $resultCode
                );
            })
            ->color('primary');
    }

    public static function afterSave($record): void
    {
        $url = TournamentRegistrationPdfService::generate($record);
        session()->flash('pdf_url', $url);
    }
}
