<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'creó', ['name']);
    }
}
