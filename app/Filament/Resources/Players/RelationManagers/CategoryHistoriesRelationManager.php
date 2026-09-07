<?php

namespace App\Filament\Resources\Players\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categoryHistories';

    protected static ?string $title = 'Historial de categorías';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha carga')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->label('Fecha efectiva')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('previousCategory.name')
                    ->label('Anterior')
                    ->placeholder('-'),

                TextColumn::make('category.name')
                    ->label('Nueva')
                    ->placeholder('-'),

                TextColumn::make('change_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'affiliation' => 'Afiliación',
                        'ranking' => 'Ranking',
                        'tournament' => 'Torneo',
                        default => $state ?? '-',
                    }),

                TextColumn::make('status')
                    ->label('Estado')
                    ->getStateUsing(function ($record): string {
                        if ($record->source !== 'manual') {
                            return 'Registrado';
                        }

                        return $record->applied_at
                            ? 'Aplicado'
                            : 'Pendiente';
                    })
                    ->badge()
                    ->color(
                        fn (string $state): string => match ($state) {
                            'Aplicado' => 'success',
                            'Pendiente' => 'warning',
                            'Registrado' => 'gray',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('notes')
                    ->label('Observaciones')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
