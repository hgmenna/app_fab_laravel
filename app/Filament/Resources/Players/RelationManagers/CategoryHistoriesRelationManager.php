<?php

namespace App\Filament\Resources\Players\RelationManagers;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class CategoryHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categoryHistories';

    protected static ?string $title = 'Historial de categorías';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('season')
                    ->label('Temporada')
                    ->numeric()
                    ->disabled(),

                Select::make('previous_category_id')
                    ->label('Categoría anterior')
                    ->relationship('previousCategory', 'name')
                    ->disabled(),

                Select::make('category_id')
                    ->label('Categoría nueva')
                    ->relationship('category', 'name')
                    ->disabled(),

                Select::make('source')
                    ->label('Origen')
                    ->options([
                        'affiliation' => 'Afiliación',
                        'ranking' => 'Ranking',
                        'tournament' => 'Torneo',
                        'manual' => 'Cambio manual',
                        'season_promotion' => 'Ascenso de temporada',
                    ])
                    ->disabled(),

                TextInput::make('change_type')
                    ->label('Tipo')
                    ->disabled(),

                DatePicker::make('effective_date')
                    ->label('Fecha efectiva'),

                Textarea::make('reason')
                    ->label('Motivo')
                    ->rows(3),

                Textarea::make('notes')
                    ->label('Observaciones')
                    ->rows(3),
            ]);
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
                        if (! in_array($record->source, ['manual', 'season_promotion'], true)) {
                            return 'Registrado';
                        }

                        return $record->applied_at ? 'Aplicado' : 'Pendiente';
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
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                ])
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
