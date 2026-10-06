<?php

namespace App\Filament\Resources\ThreeCushionRankings;

use App\Filament\Resources\ThreeCushionRankings\Pages\ListThreeCushionRankings;
use App\Filament\Resources\ThreeCushionRankings\Tables\ThreeCushionRankingsTable;
use App\Models\ThreeCushionRanking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ThreeCushionRankingResource extends Resource
{
    protected static ?string $model = ThreeCushionRanking::class;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión Deportiva';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Ranking Carambola 3 Bandas';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return ThreeCushionRankingsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListThreeCushionRankings::route('/')];
    }
}
