<?php

namespace App\Filament\Actions;

use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;

class GlobalEditAction extends EditAction
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('Editar')
            ->icon(Heroicon::PencilSquare)
            ->color('primary');
    }
}

