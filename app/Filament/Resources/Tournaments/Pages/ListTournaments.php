<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Tournament;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use Filament\Schemas\Schema;
use App\Models\TournamentRegistration;
use App\Services\AdminNotifier;
use Filament\Schemas\Components\Group;

class ListTournaments extends ListRecords
{
    protected static string $resource = TournamentResource::class;
    protected static ?string $title = 'Torneos';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo Torneo'), 
            Action::make('inscripcion_rapida')
                ->label('Inscribirme a un Torneo')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->modalHeading('Nueva Inscripción')
                ->modalWidth('4xl')
                // 1. CRÍTICO: Establece el modelo para que las relaciones player y instance funcionen [1, 2]
                ->model(TournamentRegistration::class)
                ->form(fn (Schema $schema) => [
                    // 2. Selector del Torneo
                    Select::make('tournament_id')
                        ->label('Seleccione el Torneo')
                        ->options(fn () => 
                            Tournament::where('registration_open_at', '>=', now() && 'registration_close_at', '<=', now()) // Solo vigentes
                                ->pluck('name', 'id')
                        )
                        ->required()
                        ->live()
                        ->searchable(),

                    // 3. Contenedor dinámico que inyecta el formulario existente
                    Group::make()
                        ->schema(function (Get $get, Schema $subSchema) {
                            $tournamentId = $get('tournament_id');
                            if (! $tournamentId) return [];

                            $tournament = Tournament::find($tournamentId);
                            if (! $tournament) return [];

                            // ENVIAR EL OBJETO: Usamos el subSchema (que ya está vinculado a Livewire)
                            // Esto evita el error makeGetUtility() [Fuente 30]
                            return TournamentRegistrationForm::configure($subSchema, $tournament)
                                ->getComponents();
                        })
                ])
                ->action(function (array $data) {
                    // 4. Guardado manual del registro
                    $registration = TournamentRegistration::create($data);

                    // 5. Notificación al administrador [Fuente 6, 24]
                    $tournamentName = $registration->tournament?->name ?? 'el torneo';
                    AdminNotifier::send(
                        null, 
                        $registration, 
                        'inscribió (vía acceso rápido)', 
                        ['player.last_name', 'player.first_name'], 
                        "el torneo {$tournamentName}"
                    );
                })
                ->successNotificationTitle('Inscripción realizada con éxito'),         
        ];
    }
}
