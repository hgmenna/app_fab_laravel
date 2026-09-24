<?php

namespace App\Filament\Resources\TournamentRegistrations\Schemas;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use App\Models\Category;
use App\Models\GeneralRanking;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentSlot;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TournamentRegistrationForm
{
    public static function configure(Schema $schema, ?Tournament $tournament): Schema
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
                    ->relationship(
                        name: 'player',
                        titleAttribute: 'full_name',
                        modifyQueryUsing: function ($query, Get $get) use ($tournament) {
                            $selectedTournament = $tournament ?? Tournament::find($get('tournament_id'));

                            if (! $selectedTournament) {
                                return $query->whereRaw('1 = 0');
                            }

                            $enabledCategoryIds = collect($selectedTournament->categories ?? [])
                                ->map(fn ($id) => (int) $id)
                                ->filter()
                                ->values();

                            if ($enabledCategoryIds->isEmpty()) {
                                return $query->whereRaw('1 = 0');
                            }

                            $enabledCategories = Category::query()
                                ->whereIn('id', $enabledCategoryIds)
                                ->get(['id', 'code']);

                            /*
                        * Master y Nacional se validan contra el Ranking General vigente.
                        */
                            $rankingCodes = $enabledCategories
                                ->whereIn('code', ['M', 'N'])
                                ->pluck('code')
                                ->values();

                            /*
                        * Las demás categorías se validan contra la categoría
                        * permanente del jugador.
                        */
                            $permanentCategoryIds = $enabledCategories
                                ->whereNotIn('code', ['M', 'N'])
                                ->pluck('id')
                                ->map(fn ($id) => (int) $id)
                                ->values();

                            /*
                        * Regla obligatoria para todos:
                        * solamente jugadores habilitados para competir.
                        */
                            $query->where('is_enabled_to_compete', true);

                            $query->where(function ($q) use ($rankingCodes, $permanentCategoryIds) {
                                $hasCondition = false;

                                if ($permanentCategoryIds->isNotEmpty()) {
                                    $q->whereIn('category_id', $permanentCategoryIds);
                                    $hasCondition = true;
                                }

                                if ($rankingCodes->isNotEmpty()) {
                                    $rankingQuery = GeneralRanking::query()
                                        ->select('player_id')
                                        ->whereIn('category', $rankingCodes);

                                    if ($hasCondition) {
                                        $q->orWhereIn('id', $rankingQuery);
                                    } else {
                                        $q->whereIn('id', $rankingQuery);
                                    }
                                }
                            });

                            return $query;
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['last_name', 'first_name'])
                    ->preload()
                    ->required()
                    ->rules([
                        function (Get $get, $record) { // <--- Inyectamos $record aquí
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $tournamentId = $get('tournament_id') ?? request()->route('record');

                                if (! $tournamentId) {
                                    return;
                                }

                                $exists = TournamentRegistration::where('tournament_id', $tournamentId)
                                    ->where(function ($query) use ($value) {
                                        $query->where('player_id', $value)
                                            ->orWhere('partner_player_id', $value);
                                    })
                                    // Si el registro existe (estamos editando), lo excluimos de la validación
                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                    ->exists();

                                if ($exists) {
                                    $fail('Este jugador ya está inscripto en este torneo.');
                                }
                            };
                        },
                    ])
                    ->live(),

                Select::make('partner_player_id')
                    ->label('Segundo integrante')
                    ->relationship(
                        name: 'partner',
                        titleAttribute: 'full_name',
                        modifyQueryUsing: function ($query, Get $get) use ($tournament) {
                            $selectedTournament = $tournament ?? Tournament::find($get('tournament_id'));

                            if (! $selectedTournament) {
                                return $query->whereRaw('1 = 0');
                            }

                            $enabledCategories = Category::query()
                                ->whereIn('id', collect($selectedTournament->categories ?? [])->map(fn ($id) => (int) $id))
                                ->get(['id', 'code']);

                            $rankingCodes = $enabledCategories
                                ->whereIn('code', ['M', 'N'])
                                ->pluck('code');

                            $permanentCategoryIds = $enabledCategories
                                ->whereNotIn('code', ['M', 'N'])
                                ->pluck('id');

                            $query
                                ->where('is_enabled_to_compete', true)
                                ->whereKeyNot($get('player_id'))
                                ->where(function ($eligibleQuery) use ($rankingCodes, $permanentCategoryIds) {
                                    if ($permanentCategoryIds->isNotEmpty()) {
                                        $eligibleQuery->whereIn('category_id', $permanentCategoryIds);
                                    }

                                    if ($rankingCodes->isNotEmpty()) {
                                        $rankingQuery = GeneralRanking::query()
                                            ->select('player_id')
                                            ->whereIn('category', $rankingCodes);

                                        if ($permanentCategoryIds->isNotEmpty()) {
                                            $eligibleQuery->orWhereIn('id', $rankingQuery);
                                        } else {
                                            $eligibleQuery->whereIn('id', $rankingQuery);
                                        }
                                    }
                                });

                            return $query;
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['last_name', 'first_name'])
                    ->preload()
                    ->visible(function (Get $get) use ($tournament): bool {
                        $selectedTournament = $tournament ?? Tournament::find($get('tournament_id'));

                        return $selectedTournament?->type?->participation_mode === 'pairs';
                    })
                    ->required(function (Get $get) use ($tournament): bool {
                        $selectedTournament = $tournament ?? Tournament::find($get('tournament_id'));

                        return $selectedTournament?->type?->participation_mode === 'pairs';
                    })
                    ->rules([
                        function (Get $get, $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                if (! $value) {
                                    return;
                                }

                                if ((int) $value === (int) $get('player_id')) {
                                    $fail('Los integrantes de la pareja deben ser jugadores diferentes.');

                                    return;
                                }

                                $tournamentId = $get('tournament_id') ?? request()->route('record');

                                if (! $tournamentId) {
                                    return;
                                }

                                $exists = TournamentRegistration::query()
                                    ->where('tournament_id', $tournamentId)
                                    ->where(function ($query) use ($value) {
                                        $query->where('player_id', $value)
                                            ->orWhere('partner_player_id', $value);
                                    })
                                    ->when($record, fn ($query) => $query->whereKeyNot($record->id))
                                    ->exists();

                                if ($exists) {
                                    $fail('Este jugador ya está inscripto en este torneo.');
                                }
                            };
                        },
                    ])
                    ->live(),

                Select::make('tournament_slot_id')
                    ->label('Horario')
                    ->options(function (Get $get, $livewire) {
                        // 1. Determinar el torneo según el contexto (Página de relación vs Formulario independiente)
                        if (TournamentRegistrationResource::isNested($livewire)) {
                            // En modo nested (pestaña de inscripciones), el registro viene del componente Livewire
                            $t = $livewire->getOwnerRecord();
                        } else {
                            // En modo independiente, el torneo se busca por el ID seleccionado en el formulario
                            $tournamentId = $get('tournament_id');
                            $t = Tournament::find($tournamentId);
                        }

                        if (! $t) {
                            return [];
                        }

                        // 2. Obtener y filtrar los horarios (slots) del torneo
                        $user = Auth::user();
                    $requiredPlaces = 1;

                        return $t->slots
                            ->filter(fn (TournamentSlot $slot) => $slot->starts_at !== null
                                && $slot->max_players !== null
                            )
                            ->when(
                                ($user?->name ?? null) !== 'super-admin', // El super-admin puede ver todos los horarios
                                fn ($slots) => $slots->filter(
                                    fn (TournamentSlot $slot) => $slot->max_players - $slot->occupiedPlaces() >= $requiredPlaces
                                )
                            )
                            ->mapWithKeys(function (TournamentSlot $slot) {
                                $inscriptos = $slot->occupiedPlaces();

                                $max = $slot->max_players;
                                $restantes = $max - $inscriptos;

                                // Definimos el texto que verá el usuario
                                $badge = $restantes > 0 ? "Cupos: {$restantes}" : 'COMPLETO';
                                $label = "{$slot->name} — {$inscriptos}/{$max} ({$badge})";

                                // Retornamos un array simple [ID => Texto] para evitar errores de inserción SQL
                                return [$slot->id => $label];
                            });
                    })
                    ->live()
                    // 3. Deshabilitar opciones sin cupos de forma segura para usuarios que no son super-admin
                    ->disableOptionWhen(function (string $value) {
                        $user = Auth::user();
                        if ($user?->name === 'super-admin') {
                            return false;
                        }

                        /** @var TournamentSlot $slot */
                        $slot = TournamentSlot::query()
                            ->with('tournament.type')
                            ->find($value);

                    $requiredPlaces = 1;

                        return $slot
                            && $slot->max_players - $slot->occupiedPlaces() < $requiredPlaces;
                    })
                    // 4. Mostrar una advertencia si el torneo no tiene cupos disponibles en ningún horario
                    ->hint(function (Get $get, $livewire) {
                        $t = TournamentRegistrationResource::isNested($livewire)
                            ? $livewire->getOwnerRecord()
                            : Tournament::find($get('tournament_id'));

                        if (! $t) {
                            return null;
                        }

                        $configuredSlots = $t->slots->filter(
                            fn (TournamentSlot $slot) => $slot->starts_at !== null
                                && $slot->max_players !== null
                        );

                        if ($configuredSlots->isEmpty()) {
                            return 'Este torneo no utiliza horarios de inscripción.';
                        }

                    $requiredPlaces = 1;
                        $noCupos = $configuredSlots->every(fn (TournamentSlot $slot) => $slot->max_players - $slot->occupiedPlaces() < $requiredPlaces
                        );

                        return $noCupos ? 'Este torneo no tiene cupos disponibles.' : null;
                    }
                    ),

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
                        if (! $t) {
                            $t = TournamentRegistrationResource::isNested($livewire)
                                ? $livewire->ownerRecord
                                : Tournament::find($get('tournament_id'));
                        }

                        return $t?->is_payment_enabled ?? false;
                    })
                    ->required(function (Get $get) use ($tournament) {
                        $t = $tournament ?? Tournament::find($get('tournament_id'));

                        $userAuth = Auth::user();
                        if ($userAuth?->name === 'super-admin') {
                            return false;
                        }

                        if (! $t || ! $t->is_payment_enabled) {
                            return false;
                        }

                        $playerId = $get('player_id');
                        if (! $playerId) {
                            return true;
                        }

                        $player = Player::find($playerId);
                        if (! $player) {
                            return true;
                        }

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
                    ->disabled(fn () => ! Auth::user()?->can('UpdateStatusTournament') ?? true)
                    // Asegura que el valor se envíe aunque esté deshabilitado
                    ->dehydrated(true),
            ]);
    }
}
