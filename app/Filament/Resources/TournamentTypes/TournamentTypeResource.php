<?php

namespace App\Filament\Resources\TournamentTypes;

use App\Filament\Resources\TournamentTypes\Pages\CreateTournamentType;
use App\Filament\Resources\TournamentTypes\Pages\EditTournamentType;
use App\Filament\Resources\TournamentTypes\Pages\ListTournamentTypes;
use App\Filament\Resources\TournamentTypes\Schemas\TournamentTypeForm;
use App\Filament\Resources\TournamentTypes\Tables\TournamentTypesTable;
use App\Models\TournamentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TournamentTypeResource extends Resource
{
    protected static ?string $model = TournamentType::class;
    protected static string|UnitEnum|null $navigationGroup = 'Torneos';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Tipos de torneo';
    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TournamentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentTypesTable::configure($table);
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
            'index' => ListTournamentTypes::route('/'),
            'create' => CreateTournamentType::route('/create'),
            'edit' => EditTournamentType::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
