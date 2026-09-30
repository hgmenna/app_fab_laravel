<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('discipline_id')
                    ->relationship('discipline', 'name', fn ($query) => Auth::user()?->scopeDisciplineQuery($query, 'Create:Category'))
                    ->label('Disciplina')
                    ->searchable()
                    ->preload()
                    ->default(fn () => Auth::user()?->defaultDisciplineId('Create:Category'))
                    ->required(),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Código')
                    ->helperText('Debe ser único dentro de la disciplina.')
                    ->maxLength(50),
                TextInput::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }
}
