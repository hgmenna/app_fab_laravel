<?php

namespace App\Filament\Resources\Tournaments\Pages\Concerns;

use App\Exceptions\TournamentRegulationBlockedException;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

trait InteractsWithTournamentRegulationModal
{
    public array $regulatoryModalConflicts = [];

    public bool $regulatoryModalForSuperAdmin = false;

    protected function regulatoryConflictAction(): Action
    {
        return Action::make('regulatoryConflict')
            ->label('Conflicto reglamentario')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger')
            ->extraAttributes(['class' => 'hidden'])
            ->modalHeading('No se puede generar el torneo')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalWidth('2xl')
            ->modalContent(fn (): HtmlString => $this->regulatoryModalContent())
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    protected function showRegulatoryConflictModal(TournamentRegulationBlockedException $exception): never
    {
        $this->regulatoryModalConflicts = $exception->evaluation->conflicts;
        $this->regulatoryModalForSuperAdmin = $exception->isSuperAdmin;
        $this->mountAction('regulatoryConflict');
        $this->halt();
    }

    private function regulatoryModalContent(): HtmlString
    {
        $items = collect($this->regulatoryModalConflicts)
            ->map(fn (array $conflict): string => '<li class="mb-3">'.e($conflict['message'] ?? 'Conflicto reglamentario').'</li>')
            ->implode('');

        $instruction = $this->regulatoryModalForSuperAdmin
            ? 'El torneo no fue creado. Para continuar por fuerza mayor, cerrá este aviso, activá «Autorizar excepción reglamentaria» e ingresá el motivo obligatorio.'
            : 'El torneo no fue creado. Cerrá este aviso y corregí los datos o completá las verificaciones de distancia solicitadas.';

        return new HtmlString(
            '<div class="space-y-4">'
            .'<p class="font-semibold text-danger-700">Se detectaron los siguientes incumplimientos:</p>'
            .'<ul class="list-disc pl-6">'.$items.'</ul>'
            .'<div class="rounded-lg bg-danger-50 p-4 font-medium text-danger-800">'.e($instruction).'</div>'
            .'</div>'
        );
    }
}
