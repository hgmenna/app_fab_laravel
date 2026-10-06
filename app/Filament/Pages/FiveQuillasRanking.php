<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\RankingGeneralWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FiveQuillasRanking extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Gestión Deportiva';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Ranking 5 Quillas';

    protected static ?string $title = 'Ranking Circuito Argentino de 5 Quillas';

    protected static ?string $slug = 'ranking-5-quillas';

    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [RankingGeneralWidget::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
