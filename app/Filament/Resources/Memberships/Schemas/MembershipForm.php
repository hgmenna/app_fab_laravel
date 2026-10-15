<?php

namespace App\Filament\Resources\Memberships\Schemas;

use App\Models\Discipline;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MembershipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('year')
                ->label('Año')
                ->integer()
                ->minValue(2020)
                ->maxValue(2100)
                ->default(now()->year)
                ->required(),
            Select::make('discipline_id')
                ->label('Disciplina')
                ->options(fn (): array => Discipline::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('amount')
                ->label('Importe anual')
                ->numeric()
                ->prefix('$')
                ->minValue(0)
                ->required(),
            DatePicker::make('due_date')
                ->label('Fecha de cierre')
                ->helperText('A partir de esta fecha se inhabilita a quien no haya renovado.')
                ->required(),
            Toggle::make('active')->label('Activa')->default(true)->required(),
            Textarea::make('notes')->label('Observaciones')->columnSpanFull(),
        ]);
    }
}
