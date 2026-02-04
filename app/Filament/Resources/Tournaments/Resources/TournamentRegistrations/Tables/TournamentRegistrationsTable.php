<?php

namespace App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use App\Models\TournamentInstance;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Models\TournamentRegistration;

use function PHPUnit\Framework\isNull;

class TournamentRegistrationsTable
{
    public static function configure(Table $table, $tournament): Table
    {
        return $table
            ->columns([
                 TextColumn::make('player.last_name')
                    ->label('Apellido')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('player.first_name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('player.club.name')
                    ->label('Club')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('player.category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tournamentInstance.description')
                ->label('Posicion'),

            TextColumn::make('points')
                ->label('Puntos')
                ->formatStateUsing(fn ($record) => $record->points !== null
                    ? number_format($record->points, 2)
                    : '—'),

                ImageColumn::make('payment_file')
                    ->label('Pago')
                    ->square(60),

            ])
            ->filters([
                SelectFilter::make('tournament_slot_id')
                    ->label('Horario')
                    ->options(
                        $tournament->slots->pluck('name', 'id')
                    ),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
        ])

            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
                Action::make('asignarInstancia')
                    ->label('Asignar Posicion')
                    ->modalHeading('Asignar Posicion y calcular puntos')
                    ->form([
                        Select::make('tournament_instance_id')
                            ->label('Instancia')
                            ->options(
                                TournamentInstance::pluck('description', 'id')->toArray()
                            )
                            ->nullable(), // permitir null
                    ])
                    ->action(function (array $data, TournamentRegistration $record) {

                        // Guardar instancia (puede ser null)
                        $record->tournament_instance_id = $data['tournament_instance_id'] ?? null;
                        $record->save();

                        // Recargar relaciones para evitar usar la instancia vieja en memoria
                        $record->refresh();

                        // Si no hay instancia, puntos = null
                        if (! $record->tournament_instance_id) {
                            $record->points = null;
                        } else {
                            $record->points = $record->calculatePoints();
                        }

                        $record->save();
                    })
                    ->color('primary'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
