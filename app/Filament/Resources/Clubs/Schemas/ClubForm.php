<?php

namespace App\Filament\Resources\Clubs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClubForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('short_name')
                    ->default(null),
                TextInput::make('logo_path')
                    ->default(null),
                TextInput::make('website')
                    ->default(null),
                TextInput::make('mail_contact')
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('address')
                    ->default(null),
                TextInput::make('city_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
