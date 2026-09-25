<?php

namespace App\Filament\Resources\Tournaments\Pages\Concerns;

use App\Exceptions\TournamentRegulationBlockedException;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

trait InteractsWithTournamentRegulationModal
{
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
            ->modalContent(function (Action $action): HtmlString {
                $arguments = $action->getArguments();

                return $this->regulatoryModalContent(
                    $arguments['conflicts'] ?? [],
                    (bool) ($arguments['is_super_admin'] ?? false),
                );
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    protected function showRegulatoryConflictModal(TournamentRegulationBlockedException $exception): never
    {
        $this->mountAction('regulatoryConflict', [
            'conflicts' => $exception->evaluation->conflicts,
            'is_super_admin' => $exception->isSuperAdmin,
        ]);
        $this->halt();
    }

    private function regulatoryModalContent(array $conflicts, bool $isSuperAdmin): HtmlString
    {
        $cards = collect($conflicts)
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
                $categoriesCollection = collect($conflict['shared_categories'] ?? [])->filter()->values();
                $categories = $categoriesCollection->implode(', ') ?: 'Ninguna';
                $categoryLabel = $categoriesCollection->count() === 1
                    ? 'Categoría superpuesta'
                    : 'Categorías superpuestas';
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
                    ? '<a style="color: #2563eb; font-weight: 700; text-decoration: underline;" href="'.e($route['google_maps_url']).'" target="_blank" rel="noopener noreferrer">Abrir ruta en Google Maps</a>'
                    : 'No aplica';

                $rows = [
                    ['Validación', $ruleLabel],
                    ['Torneo existente', $conflict['conflicting_tournament'] ?? 'Sin informar'],
                    ['Fecha del torneo', $dates],
                    ['Club', $conflict['club'] ?? 'Sin informar'],
                    ['Provincia', $conflict['province'] ?? 'Sin informar'],
                    [$categoryLabel, $categories],
                ];

                if ($isDistanceRule) {
                    $rows[] = ['Distancia real', $distance];
                    $rows[] = ['Distancia mínima permitida', $minimumDistance];
                    $rows[] = ['Comprobante de distancia', $evidence];
                    $rows[] = ['Dirección de origen', $route['origin_address'] ?? 'Sin informar'];
                    $rows[] = ['Dirección de destino', $route['destination_address'] ?? 'Sin informar'];
                }

                $tableRows = collect($rows)
                    ->map(fn (array $row): string => '<tr>'
                        .'<th scope="row" style="width: 42%; padding: 10px 12px; border: 1px solid #d1d5db; background: #f3f4f6; color: #1f2937; text-align: left; font-size: 14px; font-weight: 700; vertical-align: top;">'.e($row[0]).'</th>'
                        .'<td style="padding: 10px 12px; border: 1px solid #d1d5db; background: #ffffff; color: #111827; font-size: 14px; font-weight: 600; vertical-align: top;">'.e((string) $row[1]).'</td>'
                        .'</tr>')
                    ->implode('');

                $mapsRow = $isDistanceRule
                    ? '<div style="padding: 10px 12px; border: 1px solid #d1d5db; border-top: 0; background: #eff6ff; font-size: 14px;">'.$mapsLink.'</div>'
                    : '';

                $resultStyle = $rule === 'manual_distance_required'
                    ? 'background: #fef3c7; color: #92400e; border: 1px solid #f59e0b;'
                    : 'background: #fee2e2; color: #991b1b; border: 1px solid #ef4444;';

                return '<section style="margin-bottom: 18px; overflow: hidden; border: 2px solid #9ca3af; border-radius: 10px; background: #ffffff;">'
                    .'<div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; padding: 12px; background: #e5e7eb;">'
                    .'<h3 style="margin: 0; color: #111827; font-size: 16px; font-weight: 800;">Conflicto '.($index + 1).': '.e($conflict['conflicting_tournament'] ?? 'Torneo existente').'</h3>'
                    .'<span style="'.$resultStyle.' border-radius: 9999px; padding: 5px 10px; font-size: 12px; font-weight: 800;">'.e($result).'</span>'
                    .'</div>'
                    .'<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;">'.$tableRows.'</table></div>'
                    .$mapsRow
                    .'</section>';
            })
            ->implode('');

        $instruction = $isSuperAdmin
        ? 'Corregir los datos o autorizar una excepción por fuerza mayor e ingresar el motivo obligatorio.'
        : 'Corregir los datos o completar las verificaciones de distancia pendientes.';

        return new HtmlString(
            '<div>'
            .$cards
            .'<div style="display: grid; grid-template-columns: minmax(130px, auto) 1fr; gap: 10px; padding: 12px; border: 1px solid #93c5fd; border-radius: 8px; background: #eff6ff; font-size: 14px;">'
            .'<span style="color: #1e3a8a; font-weight: 800;">Acción requerida</span>'
            .'<span style="color: #1f2937; font-weight: 600;">'.e($instruction).'</span>'
            .'</div>'
            .'</div>'
        );
    }
}
