<?php

namespace App\Filament\Resources\TournamentInstances;

use App\Filament\Resources\TournamentInstances\Pages\CreateTournamentInstance;
use App\Filament\Resources\TournamentInstances\Pages\EditTournamentInstance;
use App\Filament\Resources\TournamentInstances\Pages\ListTournamentInstances;
use App\Filament\Resources\TournamentInstances\Schemas\TournamentInstanceForm;
use App\Filament\Resources\TournamentInstances\Tables\TournamentInstancesTable;
use App\Models\TournamentInstance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TournamentInstanceResource extends Resource
{
    protected static ?string $model = TournamentInstance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TournamentInstanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentInstancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTournamentInstances::route('/'),
            'create' => CreateTournamentInstance::route('/create'),
            'edit' => EditTournamentInstance::route('/{record}/edit'),
        ];
    }
}
