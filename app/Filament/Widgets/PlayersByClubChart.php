<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Player;
use App\Models\Federation;
use Filament\Forms\Components\Select;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Filament\Schemas\Schema;

class PlayersByClubChart extends ChartWidget
{

    use HasFiltersSchema;

    protected ?string $heading = 'Afiliados por Club';
    protected ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 'full';

    

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('federationId')
                ->label('Federación')
                ->options(fn () => Federation::pluck('short_name', 'id')->toArray())
                ->placeholder('Todas'),
        ]);
    }

     public function updatedFederationFilter(): void
    {
        $this->refresh();
    }

    protected function getData(): array
    {

        $federationId = $this->filters['federationId'] ?? null;
        $query = Player::query()
            ->where('is_enabled_to_compete', true)
            ->selectRaw('clubs.name as club_name, COUNT(players.id) as total')
            ->join('clubs', 'players.club_id', '=', 'clubs.id')
            ->join('cities', 'clubs.city_id', '=', 'cities.id')
            ->join('states', 'cities.state_id', '=', 'states.id')
            ->join('federations', 'states.federation_id', '=', 'federations.id')
            ->groupBy('clubs.name')
            ->orderBy('clubs.name', 'asc');

        if ($federationId) {
            $query->whereHas('club.city.state.federation', function ($q) use ($federationId) {
                $q->where('id', $federationId);
            });
        }

        $rows = $query->get();

        $labels = $rows->pluck('club_name')->toArray();
        $data   = $rows->pluck('total')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Jugadores afiliados',
                    'data' => $data,
                    'backgroundColor' => $this->generateColors(count($data)),
                    'borderColor' => '#111827',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];

    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function generateColors(int $count): array
    {
        $palette = [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(249, 115, 22, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
            'rgba(234, 179, 8, 0.8)',
        ];

        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $palette[$i % count($palette)];
        }

        return $colors;
    }

}
