<?php

namespace App\Filament\Resources\ThreeCushionRankings\Pages;

use App\Filament\Resources\ThreeCushionRankings\ThreeCushionRankingResource;
use Filament\Resources\Pages\ListRecords;

class ListThreeCushionRankings extends ListRecords
{
    protected static string $resource = ThreeCushionRankingResource::class;

    public function getTitle(): string
    {
        return 'Ranking Carambola 3 Bandas por categoría';
    }
}
