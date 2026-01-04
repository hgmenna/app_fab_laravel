<?php

namespace App\Filament\Resources\Disciplines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DisciplineForm
{
    public static function getFormSchema(): array
    {
            return [
                TextInput::make('name')
                    ->required()
                    ->label('Nombre'),
                TextInput::make('code')
                    ->default(null)
                    ->label('Abreviatura')
                    ->string(),
                TextInput::make('short_name')
                    ->default(null)
                    ->label('Nombre corto'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('scoring_rules')
                    ->label('Reglas de puntuación')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('active')
                    ->label('Activo')
                    ->required(),
            ];

    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::getFormSchema());
    }
}
