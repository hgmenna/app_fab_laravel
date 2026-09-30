<?php

namespace App\Filament\Resources\TournamentRegistrations\Pages;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use App\Mail\TournamentRegistrationNotification;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditTournamentRegistration extends EditRecord
{
    use HasTabs;

    protected static string $resource = TournamentRegistrationResource::class;

    protected static ?string $title = 'Inscripción';

    protected function getHeaderActions(): array
    {
        return [
            AdminNotifier::notifyAction(
                DeleteAction::make(),
                $this,
                'eliminó la inscripción de',
                'participant_names',
                'Inscripciones a torneos',
            ),
            AdminNotifier::notifyAction(
                ForceDeleteAction::make(),
                $this,
                'eliminó definitivamente la inscripción de',
                'participant_names',
                'Inscripciones a torneos',
            ),
            AdminNotifier::notifyAction(
                RestoreAction::make(),
                $this,
                'restauró la inscripción de',
                'participant_names',
                'Inscripciones a torneos',
            ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getSavedNotificationRedirectUrl(): ?string
    {
        return session('pdf_url') ?? null;
    }

    protected function afterSave(): void
    {
        $tournamentName = $this->record->tournament?->name ?? 'el torneo';

        Mail::to(AdminNotifier::recipientEmails())
            ->send(new TournamentRegistrationNotification($this->record, 'Actualización de inscripción'));

        AdminNotifier::send(
            $this,
            $this->record,
            'modificó la inscripción de',
            'participant_names',
            "el torneo {$tournamentName}",
            false,
        );
    }
}
