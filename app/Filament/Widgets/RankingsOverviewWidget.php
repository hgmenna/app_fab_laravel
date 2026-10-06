<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\FiveQuillasRanking;
use App\Filament\Resources\ThreeCushionRankings\ThreeCushionRankingResource;
use App\Models\GeneralRanking;
use App\Models\ThreeCushionRanking;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class RankingsOverviewWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.rankings-overview-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $panelId = Filament::getCurrentPanel()?->getId();
        $threeCushionSeason = ThreeCushionRanking::query()->max('season');
        $threeCushionQuery = ThreeCushionRanking::query()
            ->when($threeCushionSeason, fn ($query) => $query->where('season', $threeCushionSeason));

        return [
            'fiveQuillasTop' => GeneralRanking::query()->orderBy('RG')->limit(3)->get(),
            'fiveQuillasUrl' => FiveQuillasRanking::getUrl(panel: $panelId),
            'threeCushionUrl' => ThreeCushionRankingResource::getUrl('index', panel: $panelId),
            'threeCushionSeason' => $threeCushionSeason,
            'threeCushionPlayers' => (clone $threeCushionQuery)->distinct()->count('player_id'),
            'threeCushionCategories' => (clone $threeCushionQuery)->distinct()->count('category_id'),
        ];
    }
}
