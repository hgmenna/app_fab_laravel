<?php

namespace App\Filament\Resources\Tournaments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Filament\Resources\Tournaments\Pages\ViewRegistrations;
use BladeUI\Icons\Components\Icon;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Tabs;
use Filament\Support\View\Components\ToggleComponent;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ToggleColumn;

class TournamentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Torneo')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),
                    
                TextColumn::make('type.name')
                    ->label('Tipo')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('venue.name')
                    ->label('Club organizador')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date()
                    ->sortable()
                    ->alignCenter(),    
    
                TextColumn::make('registrations_count')
                    ->label('Inscriptos')
                    ->counts('registrations')
                    ->alignCenter(),

                ToggleColumn::make('is_payment_enabled')
                    ->label('Pago online')
                    ->onColor('success')
                    ->offColor('danger')
                    ->alignCenter(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver')
                    ->iconButton(),
                EditAction::make()
                    ->label('Editar')
                    ->iconButton(),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
/*
    public static function getResource(): string
    {
        return TournamentResource::class;
    }
        */

}
