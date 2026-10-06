<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardHeaderWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.dashboard-header-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'today' => now()->locale('es')->translatedFormat('l d \d\e F \d\e Y'),
        ];
    }
}
