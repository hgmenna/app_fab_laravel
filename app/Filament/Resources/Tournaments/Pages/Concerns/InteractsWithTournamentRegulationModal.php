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
            ->modalWidth('3xl')
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
        $cards = collect($this->regulatoryModalConflicts)
            ->values()
            ->map(function (array $conflict, int $index): string {
                $rule = (string) ($conflict['rule'] ?? '');
                $route = is_array($conflict['route'] ?? null) ? $conflict['route'] : [];
                $isDistanceRule = in_array($rule, ['manual_distance_required', 'minimum_distance'], true);

                $ruleLabel = match ($rule) {
                    'exclusive_dates' => 'Exclusividad durante las fechas',
                    'official_same_state' => 'Torneo oficial en la misma provincia',
                    'manual_distance_required' => 'Verificación de distancia pendiente',
                    'minimum_distance' => 'Distancia mínima reglamentaria',
                    default => 'Validación reglamentaria',
                };
                $result = match ($rule) {
                    'manual_distance_required' => 'PENDIENTE DE VERIFICACIÓN',
                    'minimum_distance' => 'DISTANCIA INSUFICIENTE',
                    default => 'BLOQUEADO',
                };
                $resultClasses = $rule === 'manual_distance_required'
                    ? 'bg-warning-50 text-warning-700 ring-warning-600/20'
                    : 'bg-danger-50 text-danger-700 ring-danger-600/20';

                $categories = collect($conflict['shared_categories'] ?? [])->filter()->implode(', ') ?: 'No aplica';
                $distance = is_numeric($route['distance_km'] ?? null)
                    ? number_format((float) $route['distance_km'], 1, ',', '.').' km'
                    : ($isDistanceRule ? 'Pendiente' : 'No aplica');
                $minimumDistance = is_numeric($route['minimum_distance_meters'] ?? null)
                    ? number_format(((float) $route['minimum_distance_meters']) / 1000, 1, ',', '.').' km'
                    : 'No aplica';
                $evidence = ! empty($route['evidence_path'])
                    ? 'Adjuntado'
                    : ($isDistanceRule ? 'Pendiente' : 'No aplica');
                $dates = trim(implode(' — ', array_filter([
                    $conflict['start_date'] ?? null,
                    $conflict['end_date'] ?? null,
                ]))) ?: 'Sin informar';
                $mapsLink = ! empty($route['google_maps_url'])
                    ? '<a class="font-semibold text-primary-600 underline hover:text-primary-500" href="'.e($route['google_maps_url']).'" target="_blank" rel="noopener noreferrer">Abrir ruta en Google Maps</a>'
                    : 'No aplica';

                $rows = [
                    ['Regla incumplida', $ruleLabel],
                    ['Torneo en conflicto', $conflict['conflicting_tournament'] ?? 'Sin informar'],
                    ['Fechas', $dates],
                    ['Club / sede', $conflict['club'] ?? 'Sin informar'],
                    ['Provincia', $conflict['province'] ?? 'Sin informar'],
                    ['Categorías superpuestas', $categories],
                    ['Distancia real', $distance],
                    ['Distancia mínima', $minimumDistance],
                    ['Comprobante', $evidence],
                ];

                if ($isDistanceRule) {
                    $rows[] = ['Dirección de origen', $route['origin_address'] ?? 'Sin informar'];
                    $rows[] = ['Dirección de destino', $route['destination_address'] ?? 'Sin informar'];
                }

                $tableRows = collect($rows)
                    ->map(fn (array $row): string => '<tr class="border-b border-gray-200 last:border-0 dark:border-white/10">'
                        .'<th scope="row" class="w-2/5 bg-gray-50 px-3 py-2 text-left text-sm font-semibold text-gray-700 dark:bg-white/5 dark:text-gray-200">'.e($row[0]).'</th>'
                        .'<td class="px-3 py-2 text-sm text-gray-950 dark:text-white">'.e((string) $row[1]).'</td>'
                        .'</tr>')
                    ->implode('');

                $mapsRow = $isDistanceRule
                    ? '<div class="border-t border-gray-200 px-3 py-2 text-sm dark:border-white/10">'.$mapsLink.'</div>'
                    : '';

                return '<section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">'
                    .'<div class="flex flex-wrap items-center justify-between gap-2 bg-gray-100 px-4 py-3 dark:bg-white/5">'
                    .'<h3 class="font-bold text-gray-950 dark:text-white">Conflicto '.($index + 1).'</h3>'
                    .'<span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset '.$resultClasses.'">'.e($result).'</span>'
                    .'</div>'
                    .'<div class="overflow-x-auto"><table class="w-full border-collapse">'.$tableRows.'</table></div>'
                    .$mapsRow
                    .'</section>';
            })
            ->implode('');

        $instruction = $this->regulatoryModalForSuperAdmin
        ? 'Corregir los datos o autorizar una excepción por fuerza mayor e ingresar el motivo obligatorio.'
        : 'Corregir los datos o completar las verificaciones de distancia pendientes.';

        return new HtmlString(
            '<div class="space-y-4">'
            .$cards
            .'<div class="grid grid-cols-[auto_1fr] gap-x-3 rounded-lg bg-gray-100 p-4 text-sm dark:bg-white/5">'
            .'<span class="font-bold text-gray-950 dark:text-white">Acción requerida</span>'
            .'<span class="text-gray-700 dark:text-gray-200">'.e($instruction).'</span>'
            .'</div>'
            .'</div>'
        );
    }
}
