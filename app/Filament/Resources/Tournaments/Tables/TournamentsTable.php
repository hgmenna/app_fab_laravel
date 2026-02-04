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
                    ->sortable(),

                TextColumn::make('type.name')
                    ->label('Tipo')
                    ->sortable(),

                TextColumn::make('venue.name')
                    ->label('Club organizador')
                    ->sortable(),

                    TextColumn::make('start_date')
                        ->label('Inicio')
                        ->date()
                        ->sortable(),
    
                    TextColumn::make('end_date')
                        ->label('Fin')
                        ->date()
                        ->sortable(),

                TextColumn::make('registrations_count')
                    ->label('Inscriptos')
                    ->counts('registrations'),

                ToggleColumn::make('is_payment_enabled')
                    ->label('Pago online')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
