<?php

namespace App\Filament\Resources\TournamentRegulationAudits\Tables;

use App\Services\TournamentRegulationAuditPdfService;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TournamentRegulationAuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('tournament_name')->label('Torneo')
                ->state(fn ($record): string => $record->tournament?->name
                    ?? data_get($record->tournament_snapshot, 'name', 'Intento bloqueado')),
            TextColumn::make('user.name')->label('Usuario')->searchable(),
            TextColumn::make('operation')->label('Operación')->formatStateUsing(fn ($state) => $state === 'create' ? 'Creación' : 'Modificación')->badge(),
            TextColumn::make('result')->label('Resultado')->formatStateUsing(fn ($state) => match ($state) {
                'approved' => 'Aprobado', 'overridden' => 'Excepción autorizada', default => 'Bloqueado',
            })->badge()->color(fn ($state) => match ($state) {
                'approved' => 'success', 'overridden' => 'warning', default => 'danger',
            }),
            IconColumn::make('overridden')->label('Excepción')->boolean(),
            TextColumn::make('override_reason')->label('Motivo')->wrap()->toggleable(),
            TextColumn::make('conflicts')->label('Incumplimientos')
                ->formatStateUsing(fn ($state): string => collect($state ?? [])->pluck('message')->implode(' | '))->wrap(),
            TextColumn::make('technical_details')
                ->label('Distancias verificadas')
                ->formatStateUsing(fn ($state): string => collect(data_get($state, 'distance_checks', []))
                    ->map(function (array $check): string {
                        $distance = isset($check['distance_km']) ? $check['distance_km'].' km' : 'Sin distancia';
                        $evidence = $check['evidence_path'] ?? 'Sin comprobante';

                        return $distance.' · '.$evidence;
                    })->implode(' | '))
                ->wrap()
                ->toggleable(isToggledHiddenByDefault: true),
        ])->recordActions([
            Action::make('downloadPdf')
                ->label('Descargar informe')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn ($record) => app(TournamentRegulationAuditPdfService::class)->download($record)),
        ])->filters([SelectFilter::make('result')->label('Resultado')->options([
            'approved' => 'Aprobado', 'blocked' => 'Bloqueado', 'overridden' => 'Excepción autorizada',
        ])]);
    }
}
