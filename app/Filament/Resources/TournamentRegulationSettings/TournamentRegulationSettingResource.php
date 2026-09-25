<?php
namespace App\Filament\Resources\TournamentRegulationSettings;

use App\Filament\Resources\TournamentRegulationSettings\Pages\CreateTournamentRegulationSetting;
use App\Filament\Resources\TournamentRegulationSettings\Pages\EditTournamentRegulationSetting;
use App\Filament\Resources\TournamentRegulationSettings\Pages\ListTournamentRegulationSettings;
use App\Filament\Resources\TournamentRegulationSettings\Schemas\TournamentRegulationSettingForm;
use App\Filament\Resources\TournamentRegulationSettings\Tables\TournamentRegulationSettingsTable;
use App\Models\TournamentRegulationSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TournamentRegulationSettingResource extends Resource
{
    protected static ?string $model = TournamentRegulationSetting::class;
    protected static string|UnitEnum|null $navigationGroup = 'Torneos';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'Control reglamentario';
    protected static ?int $navigationSort = 13;
    public static function form(Schema $schema): Schema { return TournamentRegulationSettingForm::configure($schema); }
    public static function table(Table $table): Table { return TournamentRegulationSettingsTable::configure($table); }
    public static function getPages(): array { return [
        'index' => ListTournamentRegulationSettings::route('/'),
        'create' => CreateTournamentRegulationSetting::route('/create'),
        'edit' => EditTournamentRegulationSetting::route('/{record}/edit'),
    ]; }
}
