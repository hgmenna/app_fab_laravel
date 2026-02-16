<?php

namespace App\Filament\Resources\Players\Widgets;

use App\Models\Player;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use App\Filament\Resources\Players\Traits\AppliesPlayerFilters;

class PlayerChart extends ChartWidget
{
    use AppliesPlayerFilters;

    protected ?string $heading = 'Jugadores por Categoría';

    protected static bool $isLazy = false;
    protected static bool $isReactive = true;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        // 🔥 Filament 4 modular: así se obtienen filtros y búsqueda
        $filters = $this->page->getTableFilters();
        $search  = $this->page->getTableSearch();

        // Aplicamos la misma lógica que la tabla
        $query = $this->applyPlayersFilters(Player::query(), $filters, $search);

        $data = $query
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->toArray();

        $labels = Category::whereIn('id', array_keys($data))
            ->pluck('name', 'id')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Jugadores',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_values($labels),
        ];
    }
}


