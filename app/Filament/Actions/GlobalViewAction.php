<?php

namespace App\Filament\Actions;

use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;

class GlobalViewAction extends ViewAction
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('Ver')
            ->icon(Heroicon::Eye)
            ->color('info');
    }
}

