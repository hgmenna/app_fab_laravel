<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getPageClasses(): array
    {
        return [
            ...parent::getPageClasses(),
            'fab-compact-dashboard',
        ];
    }
}
