<?php

namespace App\Livewire;

use App\Filament\Resources\Tournaments\Pages\ManageTournamentCompetition;
use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

/** Organización móvil: comparte operaciones, validaciones y conflictos con FAB, con presentación propia. */
class MobileTournamentOrganization extends ManageTournamentCompetition
{
    /** Fase seleccionada; siempre se resuelve dentro de la competencia autorizada. */
    public string $mobilePhase = '';

    /** Zona seleccionada; vacía permite consultar todas las zonas de la fase. */
    public string $mobileZone = '';

    /** Subvista de la fase final, independiente de las fases del torneo. */
    public string $mobileSection = 'bracket';

    /** Configura los formularios compartidos sin incorporar la navegación del panel web. */
    public function boot(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    /** La entrada conserva la autorización y sincronización de inscriptos del organizador web. */
    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()?->hasVerifiedEmail(), 403);
        parent::mount($record);
    }

    /** Revalida el permiso en cada acción: revocar acceso o cerrar sesión bloquea también formularios abiertos. */
    public function hydrate(): void
    {
        abort_unless(auth()->user()?->hasVerifiedEmail()
            && auth()->user()->canAccessTournament('ManageTournamentCompetition', $this->getRecord()), 403);
    }

    /** Cambiar de fase limpia filtros que pertenecían a la fase anterior. */
    public function updatedMobilePhase(): void
    {
        $this->mobileZone = '';
        $this->mobileSection = 'bracket';
    }

    /** Convierte la tabla de cinco columnas en campos verticales, conservando la validación y guardado originales. */
    public function createZonesAction(): Action
    {
        $action = parent::createZonesAction();
        $source = clone $action;

        return $action->schema(function (Schema $schema, Action $action) use ($source): ?Schema {
            $form = $source->livewire($this)->arguments($action->getArguments())->getSchema($schema);
            foreach ($form?->getComponents(withHidden: true) ?? [] as $component) {
                if (! $component instanceof Repeater) {
                    continue;
                }
                $component->table(null)->columns(1);
                foreach ($component->getDefaultChildComponents() as $field) {
                    if ($field instanceof Select && str_starts_with($field->getName(), 'player_')) {
                        $field->hiddenLabel(false)->label('Posición '.substr($field->getName(), 7));
                    }
                }
            }

            return $form;
        });
    }

    /** Renderiza exclusivamente el contenido móvil; los servicios y acciones son los mismos de FAB. */
    public function render(): View
    {
        $data = $this->getViewData();
        $phases = $data['competition']?->phases->where('is_enabled', true)->sortBy('sort_order') ?? collect();
        $phase = $phases->firstWhere('id', (int) $this->mobilePhase) ?? $phases->first();

        return view('livewire.mobile-tournament-organization', $data + [
            'mobilePhases' => $phases,
            'selectedPhase' => $phase,
        ]);
    }
}
