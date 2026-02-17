<?php

namespace App\Filament\Resources\Clubs\Tables;

use App\Filament\Resources\Clubs\ClubResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;

class ClubsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Gestión de Clubes')
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->square(40),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->limit(15)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Domicilio')
                    ->limit(15)
                    ->searchable(),
                TextColumn::make('city.name')
                    ->label('Ciudad')
                    ->limit(15)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city.state.federation.short_name')
                    ->label('Federación')
                    ->sortable()
                    ->searchable(),
                 TextColumn::make('players_count')
                    ->label('Afil')
                    ->counts('players')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('federation_id')
                    ->label('Federación')
                    ->relationship('city.state.federation', 'name'),
                SelectFilter::make('is_active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activo',
                        0 => 'Inactivo',
                    ]),
            ])
            ->recordActions([
                Action::make('Afiliados')
                    ->label('')
                    ->icon('heroicon-o-users')
                    ->modalHeading(fn ($record) => "Jugadores de {$record->name}")
                    ->modalContent(fn ($record) =>
                        view('filament.clubs.partials.players-table', [
                            'players' => $record->players()
                                ->with('category')
                                ->orderBy('last_name')
                                ->get(),
                        ])
                    )
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                EditAction::make()->label('Editar')->iconButton(),
                ViewAction::make()->label('Ver')->iconButton(),
                DeleteAction::make()->label('Eliminar')->iconButton(),
            ])
            ->headerActions([
                Action::make('descargarPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                        // Obtenemos los registros filtrados desde el componente Livewire
                        $records = $livewire->getFilteredTableQuery()->get();
                        
                        // Invocamos el método estático del Resource
                        return ClubResource::exportToPdf($records, 'Listado de Clubes');
                    }),
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
