<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Services\AdminNotifier;
use App\Services\TournamentRegulationEvaluation;
use App\Services\TournamentRegulationWorkflow;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTournament extends CreateRecord
{
    protected static string $resource = TournamentResource::class;

    private ?TournamentRegulationEvaluation $regulationEvaluation = null;

    private bool $regulationOverridden = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$data, $this->regulationEvaluation, $this->regulationOverridden] = app(TournamentRegulationWorkflow::class)
            ->validate($data, Auth::user(), 'create');

        return $data;
    }

    public static function getFormWidth(): string|int|null
    {
        return 'full';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        if ($this->regulationEvaluation) {
            app(TournamentRegulationWorkflow::class)->audit(
                $this->record,
                Auth::user(),
                'create',
                $this->regulationOverridden ? 'overridden' : 'approved',
                $this->regulationOverridden,
                $this->record->regulatory_override_reason,
                $this->record->getAttributes(),
                $this->regulationEvaluation,
            );
        }

        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'creó', ['name']);
    }
}
