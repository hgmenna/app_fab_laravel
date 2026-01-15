<?php

namespace App\Filament\Resources\TournamentRegistrations;

use App\Filament\Resources\TournamentRegistrations\Pages\CreateTournamentRegistration;
use App\Filament\Resources\TournamentRegistrations\Pages\EditTournamentRegistration;
use App\Filament\Resources\TournamentRegistrations\Pages\ListTournamentRegistrations;
use App\Filament\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Filament\Resources\TournamentRegistrations\Tables\TournamentRegistrationsTable;
use App\Models\TournamentRegistration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TournamentRegistrationResource extends Resource
{
    protected static ?string $model = TournamentRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'neme';

    public static function form(Schema $schema): Schema
    {
        return TournamentRegistrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentRegistrationsTable::configure($table);
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
            'index' => ListTournamentRegistrations::route('/'),
            'create' => CreateTournamentRegistration::route('/create'),
            'edit' => EditTournamentRegistration::route('/{record}/edit'),
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
