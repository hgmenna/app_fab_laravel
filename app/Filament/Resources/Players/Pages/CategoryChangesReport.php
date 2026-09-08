<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\PlayerCategoryHistory;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Federation;
use App\Models\Club;
use App\Models\Category;
use Filament\Actions\Action;
use App\Helpers\FabPath;
use Barryvdh\DomPDF\Facade\Pdf;

class CategoryChangesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PlayerResource::class;

    protected static ?string $title = 'Cambios de categoría';

    protected string $view = 'filament.resources.players.pages.category-changes-report';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Generar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    ini_set('memory_limit', '512M');
                    set_time_limit(300);

                    $records = $this->getFilteredTableQuery()
                        ->with([
                            'player.club.city.state.federation',
                            'previousCategory',
                            'category',
                        ])
                        ->get();

                    $pdf = Pdf::loadView(
                        'pdf.category-changes-report',
                        [
                            'records' => $records,
                            'date' => now()->format('d/m/Y H:i'),
                            'logo' => FabPath::logo(),
                            'footer_image' => FabPath::footer(),
                        ]
                    )->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        function () use ($pdf): void {
                            echo $pdf->stream();
                        },
                        'Cambios de categoria - ' . now()->format('Y-m-d') . '.pdf'
                    );
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PlayerCategoryHistory::query()
                    ->whereNotNull('previous_category_id')
                    ->with([
                        'player.club.city.state.federation',
                        'previousCategory',
                        'category',
                    ])
            )
            ->defaultSort('effective_date', 'desc')
            ->columns([
                TextColumn::make('season')
                    ->label('Temporada')
                    ->sortable()
                    ->width('90px'),

                TextColumn::make('player_name')
                    ->label('Jugador')
                    ->state(fn (PlayerCategoryHistory $record): string =>
                        trim(
                            ($record->player?->last_name ?? '')
                            . ' '
                            . ($record->player?->first_name ?? '')
                        )
                    )
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->whereHas(
                                'player',
                                fn (Builder $playerQuery) => $playerQuery
                                    ->where('last_name', 'like', "%{$search}%")
                                    ->orWhere('first_name', 'like', "%{$search}%")
                            );
                        }
                    )
                    ->wrap()
                    ->width('170px'),

                TextColumn::make('player.club.name')
                    ->label('Club / Federación')
                    ->description(
                        fn (PlayerCategoryHistory $record): string =>
                            $record->player?->club?->city?->state?->federation?->name ?? '-'
                    )
                    ->grow()
                    ->width('360px'),

                TextColumn::make('category_change')
                    ->label('Cambio de categoría')
                    ->state(
                        fn (PlayerCategoryHistory $record): string =>
                            $record->previousCategory?->name ?? '-'
                    )
                    ->description(
                        fn (PlayerCategoryHistory $record): string =>
                            '→ ' . ($record->category?->name ?? '-')
                    )
                    ->wrap()
                    ->width('180px'),

                TextColumn::make('effective_date')
                    ->label('Fecha efectiva')
                    ->date('d/m/Y')
                    ->sortable()
                    ->width('125px'),

                TextColumn::make('created_at')
                    ->label('Fecha de carga')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->width('145px'),

                TextColumn::make('source')
                    ->label('Origen')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'manual' => 'Manual',
                        'season_promotion' => 'Promoción',
                        'ranking' => 'Ranking',
                        'tournament' => 'Torneo',
                        'affiliation' => 'Afiliación',
                        default => $state ?? '-',
                    })
                    ->badge()
                    ->width('100px'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->state(function (PlayerCategoryHistory $record): string {
                        if (! in_array(
                            $record->source,
                            ['manual', 'season_promotion'],
                            true
                        )) {
                            return 'Registrado';
                        }

                        return $record->applied_at
                            ? 'Aplicado'
                            : 'Pendiente';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Aplicado' => 'success',
                        'Registrado' => 'info',
                        default => 'gray',
                    })
                    ->width('110px'),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->tooltip(
                        fn (PlayerCategoryHistory $record): ?string =>
                            $record->reason
                    )
                    ->width('150px'),

                TextColumn::make('notes')
                    ->label('Notas')
                    ->tooltip(
                        fn (PlayerCategoryHistory $record): ?string =>
                            $record->notes
                    )
                    ->width('130px'),
            ])
            
            ->filters([
            SelectFilter::make('season')
                ->label('Temporada')
                ->options(
                    PlayerCategoryHistory::query()
                        ->whereNotNull('season')
                        ->whereNotNull('previous_category_id')
                        ->distinct()
                        ->orderByDesc('season')
                        ->pluck('season', 'season')
                        ->toArray()
                ),

            SelectFilter::make('status')
                ->label('Estado')
                ->options([
                    'pending' => 'Pendiente',
                    'applied' => 'Aplicado',
                    'registered' => 'Registrado',
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;

                    return match ($value) {
                        'pending' => $query
                            ->whereIn('source', ['manual', 'season_promotion'])
                            ->whereNull('applied_at'),

                        'applied' => $query
                            ->whereIn('source', ['manual', 'season_promotion'])
                            ->whereNotNull('applied_at'),

                        'registered' => $query
                            ->whereNotIn('source', ['manual', 'season_promotion']),

                        default => $query,
                    };
                }),

            SelectFilter::make('federation')
                ->label('Federación')
                ->options(
                    Federation::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    $federationId = $data['value'] ?? null;

                    if (! $federationId) {
                        return $query;
                    }

                    return $query->whereHas(
                        'player.club.city.state',
                        fn (Builder $stateQuery) => $stateQuery
                            ->where('federation_id', $federationId)
                    );
                }),

            SelectFilter::make('club')
                ->label('Club')
                ->options(
                    Club::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    $clubId = $data['value'] ?? null;

                    if (! $clubId) {
                        return $query;
                    }

                    return $query->whereHas(
                        'player',
                        fn (Builder $playerQuery) => $playerQuery
                            ->where('club_id', $clubId)
                    );
                }),
            
            SelectFilter::make('previous_category_id')
                ->label('Categoría anterior')
                ->options(
                    Category::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable(),

            SelectFilter::make('category_id')
                ->label('Categoría nueva')
                ->options(
                    Category::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                )
                ->searchable(),

            SelectFilter::make('source')
                ->label('Origen')
                ->options([
                    'ranking' => 'Ranking General',
                    'manual' => 'Cambio manual',
                    'season_promotion' => 'Ascenso de temporada',
                ]),
            
        ]);
    }
}