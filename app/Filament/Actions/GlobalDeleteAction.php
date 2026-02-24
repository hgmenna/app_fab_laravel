<?php

namespace App\Filament\Actions;

use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;

class GlobalDeleteAction extends DeleteAction
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('Eliminar')
            ->icon(Heroicon::Trash)
            ->color('danger');
    }
}

