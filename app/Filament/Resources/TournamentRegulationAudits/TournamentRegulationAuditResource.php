<?php
namespace App\Filament\Resources\TournamentRegulationAudits;
use App\Filament\Resources\TournamentRegulationAudits\Pages\ListTournamentRegulationAudits;
use App\Filament\Resources\TournamentRegulationAudits\Tables\TournamentRegulationAuditsTable;
use App\Models\TournamentRegulationAudit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
class TournamentRegulationAuditResource extends Resource
{
    protected static ?string $model = TournamentRegulationAudit::class;
    protected static string|UnitEnum|null $navigationGroup = 'Torneos';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static ?string $navigationLabel = 'Auditoría reglamentaria';
    protected static ?int $navigationSort = 14;
    public static function table(Table $table): Table { return TournamentRegulationAuditsTable::configure($table); }
    public static function getPages(): array { return ['index' => ListTournamentRegulationAudits::route('/')]; }
}
