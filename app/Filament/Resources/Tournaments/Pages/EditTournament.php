<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Exceptions\TournamentRegulationBlockedException;
use App\Filament\Resources\Tournaments\Pages\Concerns\InteractsWithTournamentRegulationModal;
use App\Filament\Resources\Tournaments\TournamentResource;
use App\Services\AdminNotifier;
use App\Services\TournamentRegulationEvaluation;
use App\Services\TournamentRegulationWorkflow;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTournament extends EditRecord
{
    use InteractsWithTournamentRegulationModal;

    protected static string $resource = TournamentResource::class;

    protected static ?string $navigationLabel = 'Datos del torneo';

    private ?TournamentRegulationEvaluation $regulationEvaluation = null;

    private bool $regulationOverridden = false;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            [$data, $this->regulationEvaluation, $this->regulationOverridden] = app(TournamentRegulationWorkflow::class)
                ->validate($data, Auth::user(), 'update', $this->record);
        } catch (TournamentRegulationBlockedException $exception) {
            $this->showRegulatoryConflictModal($exception);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->regulatoryConflictAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        if ($this->regulationEvaluation) {
            app(TournamentRegulationWorkflow::class)->audit(
                $this->record,
                Auth::user(),
                'update',
                $this->regulationOverridden ? 'overridden' : 'approved',
                $this->regulationOverridden,
                $this->record->regulatory_override_reason,
                $this->record->getAttributes(),
                $this->regulationEvaluation,
            );
        }

        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'actualizó', ['name']);
    }
}
