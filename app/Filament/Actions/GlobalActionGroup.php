<?php

namespace App\Filament\Actions;

use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;

class GlobalActionGroup extends ActionGroup
{
    public static function make(array $actions = []): static
    {
        return parent::make($actions)
            ->label('Acciones')
            ->color('success')
            ->button()
            ->icon(Heroicon::Cog6Tooth)
            ->outlined();
    }
}