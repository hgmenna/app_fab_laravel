<?php

namespace App\Filament\Resources\TournamentRegistrations\Schemas;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentInstance;
use App\Models\TournamentRegistration;
use App\Models\TournamentSlot;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TournamentRegistrationForm
{
    public static function configure(Schema $schema, $tournament): Schema
    {
        return $schema
            ->components([
                Select::make('tournament_id')
                    ->label('Torneo')
                    ->relationship('tournament', 'name')
                    ->required()
                    ->live()
                    // Si $tournament tiene datos O si el Livewire es una página de relación, se oculta
                    ->hidden(function ($livewire) use ($tournament) {
                        return $tournament !== null || 
                            $livewire instanceof \Filament\Resources\Pages\ManageRelatedRecords ||
                            method_exists($livewire, 'getOwnerRecord');
                            dd($livewire->getOwnerRecord());
                    })
                    ->dehydrated(true),

                Select::make('player_id')
                ->label('Jugador')
                ->relationship('player', 'full_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                ->searchable(['last_name', 'first_name'])
                ->preload()
                ->required()
                /* 🔥 ÚNICO AGREGADO: filtrar por categoría habilitada + habilitados para competir */
                ->options(function (Get $get) {

                    // ID del torneo ya cargado en el formulario
                    $tournamentId = $get('tournament_id');

                    // Obtener torneo
                    $tournament = Tournament::find($tournamentId);

                    // Categorías habilitadas (array JSON)
                    $categorias = $tournament?->categories ?? [];

                    if (empty($categorias)) {
                        return [];
                    }

                    // Jugadores filtrados por categoría + habilitados para competir
                    return Player::whereIn('category_id', $categorias)
                        ->where('is_enabled_to_compete', true)
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn ($p) => [$p->id => $p->full_name]);
                })
                /* 🔥 FIN DEL ÚNICO AGREGADO */
                ->rules([
                    function (Get $get, $record) { // <--- Inyectamos $record aquí
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $tournamentId = $get('tournament_id') ?? request()->route('record'); 

                            if (!$tournamentId) {
                                return;
                            }

                            $exists = TournamentRegistration::where('tournament_id', $tournamentId)
                                ->where('player_id', $value)
                                // Si el registro existe (estamos editando), lo excluimos de la validación
                                ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                ->exists(); 

                            if ($exists) {
                                $fail('Este jugador ya está inscripto en este torneo.'); 
                            }
                        };
                    }
                ])
                ->live(),
            Select::make('tournament_slot_id')
                ->label('Horario')
                ->options(function (Get $get, $livewire) use ($tournament) {
                    // 1. Determinar el torneo según el contexto (Página de relación vs Formulario independiente)
                    if (TournamentRegistrationResource::isNested($livewire)) {
                        // En modo nested (pestaña de inscripciones), el registro viene del componente Livewire
                        $t = $livewire->getOwnerRecord(); 
                    } else {
                        // En modo independiente, el torneo se busca por el ID seleccionado en el formulario
                        $tournamentId = $get('tournament_id');
                        $t = Tournament::find($tournamentId);
                    }

                    if (!$t) {
                        return [];
                    }

                    // 2. Obtener y filtrar los horarios (slots) del torneo
                    $user = Auth::user();

                    return $t->slots
                        ->when(
                            ($user?->name ?? null) !== 'super-admin', // El super-admin puede ver todos los horarios
                            fn ($slots) => $slots->filter(
                                fn (TournamentSlot $slot) => $slot->registrations()
                                    ->where('status', '!=', 'denegado')
                                    ->count() < $slot->max_players
                            )
                        )
                        ->mapWithKeys(function (TournamentSlot $slot) {
                            $inscriptos = $slot->registrations()
                                ->where('status', '!=', 'denegado')
                                ->count();

                            $max = $slot->max_players;
                            $restantes = $max - $inscriptos;

                            // Definimos el texto que verá el usuario
                            $badge = $restantes > 0 ? "Cupos: {$restantes}" : "COMPLETO";
                            $label = "{$slot->name} — {$inscriptos}/{$max} ({$badge})";

                            // Retornamos un array simple [ID => Texto] para evitar errores de inserción SQL
                            return [$slot->id => $label];
                        });
                })
                ->required()
                ->live()
                // 3. Deshabilitar opciones sin cupos de forma segura para usuarios que no son super-admin
                ->disableOptionWhen(function (string $value) {
                    $user = Auth::user();
                    if ($user?->name === 'super-admin') {
                        return false;
                    }

                    /** @var TournamentSlot $slot */ 
                    $slot = TournamentSlot::find($value);

                    return $slot && ($slot->registrations()
                        ->where('status', '!=', 'denegado')
                        ->count() >= $slot->max_players);
                })
                // 4. Mostrar una advertencia si el torneo no tiene cupos disponibles en ningún horario
                ->hint(function (Get $get, $livewire) use ($tournament) {
                    $t = TournamentRegistrationResource::isNested($livewire)
                        ? $livewire->getOwnerRecord()
                        : Tournament::find($get('tournament_id'));

                    if (!$t) return null;

                    $noCupos = $t->slots->every(
                        fn (TournamentSlot $slot) => $slot->registrations()
                            ->where('status', '!=', 'denegado')
                            ->count() >= $slot->max_players
                    );

                    return $noCupos ? 'Este torneo no tiene cupos disponibles.' : null;
                }
            ),
            Select::make('tournament_instance_id')
                ->relationship('tournamentInstance', 'description')
                ->label('Posicion')
                ->visible(function (Get $get, $livewire) use ($tournament) {
                    // Resolvemos el torneo dinámicamente
                    $t = $tournament;
                    if (!$t) {
                        $t = TournamentRegistrationResource::isNested($livewire)
                            ? $livewire->ownerRecord // Si es dentro de un torneo
                            : Tournament::find($get('tournament_id')); // Si es independiente
                    }

                    // Si no hay torneo (aún no seleccionado), ocultamos el campo
                    if (!$t) return false;

                    $user = Auth::user();
                    if(! $user) {
                        return false; // invitado no puede editar
                    }

                    return Auth::user()->can('EditField') && $t->start_date < now();
                })
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (! $state) {
                        $set('points', null);
                        return;
                    }

                    $instance = TournamentInstance::find($state);

                    $set('points', $instance?->points_default ?? 0);
                }
            ),

            TextInput::make('points')
                ->label('Puntos')
                ->visible(function (Get $get, $livewire) use ($tournament) {
                    $t = $tournament;
                    if (!$t) {
                        $t = TournamentRegistrationResource::isNested($livewire)
                            ? $livewire->ownerRecord
                            : Tournament::find($get('tournament_id'));
                    }

                    if (!$t) return false;

                    $user = Auth::user();
                    if(! $user) {
                        return false; // invitado no puede editar
                    }

                    return Auth::user()->can('EditField') && $t->start_date < now();
                })
                ->disabled(),

            FileUpload::make('payment_file')
                ->label('Comprobante de pago')
                ->disk('public_path') // <--- Indispensable para que sea accesible vía URL
                ->visibility('public') // <--- Asegura permisos de lectura
                ->directory('pagos')
                ->openable(true)
                ->getUploadedFileNameForStorageUsing(function (Get $get, TemporaryUploadedFile $file, $livewire): string {
                    // 1. Obtener el ID del jugador seleccionado
                    $playerId = $get('player_id');
                    $player = Player::find($playerId);
                    
                    // 2. Obtener el full_name del jugador (Lógica externa a las fuentes)
                    $lastName = $player?->last_name ?? 'Apellido';
                    $firstName = $player?->first_name ?? 'Nombre';
                    
                    // 3. Obtener el nombre del torneo (desde el registro actual o mediante $get)
                    $tournamentName = $livewire->getOwnerRecord()->name;
                    $extension = $file->getClientOriginalExtension();

                    $tournamentSlug = str($tournamentName)->slug();
                    $playerSlug = str("{$lastName}_{$firstName}")->slug('_');

                    // 4. Concatenar y retornar el nombre con la extensión original
                    return "{$tournamentSlug}-{$playerSlug}.{$extension}";
                })
                // Usamos una función anónima para verificar la visibilidad dinámicamente
                ->visible(function (Get $get, $livewire) use ($tournament) {
                    // Consistencia en la resolución del torneo
                    $t = $tournament;
                    if (!$t) {
                        $t = TournamentRegistrationResource::isNested($livewire)
                            ? $livewire->ownerRecord
                            : Tournament::find($get('tournament_id'));
                    }
                    return $t?->is_payment_enabled ?? false;
                })
                ->required(function(Get $get) use ($tournament) {
                    $t = $tournament ?? Tournament::find($get('tournament_id'));

                    $userAuth = Auth::user();
                    if ($userAuth?->name === 'super-admin') return false;
                    
                    if(!$t || !$t->is_payment_enabled) {
                        return false;
                    }

                    $playerId = $get('player_id');
                    if (!$playerId) return true;

                    $player = Player::find($playerId);
                    if (!$player) return true;

                    $categoriasNoRequeridas = ['MASTER', '1ra NACIONAL'];
                    return ! in_array($player->category->name, $categoriasNoRequeridas);
                })
                ->live(),
            Select::make('status')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aprobado' => 'Aprobado',
                    'rechazado' => 'Rechazado',
                ])
                ->default('pendiente')
                // Se deshabilita si el usuario no tiene el permiso de Shield [2, 3]
                ->disabled(fn () => !Auth::user()?->can('UpdateStatusTournament') ?? true)
                // Asegura que el valor se envíe aunque esté deshabilitado
                ->dehydrated(true),
            ]);
    }
}
