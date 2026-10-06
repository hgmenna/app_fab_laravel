<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ThreeCushionRankings\Tables\ThreeCushionRankingsTable;
use App\Services\ThreeCushionRankingService;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ThreeCushionRankingWidget extends TableWidget
{
    protected static ?string $heading = 'Ranking Carambola 3 Bandas';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $service = app(ThreeCushionRankingService::class);
        $discipline = $service->discipline();

        if ($discipline) {
            $service->syncSeason((int) now()->year, (int) $discipline->id);
        }

        return ThreeCushionRankingsTable::configure($table);
    }
}
