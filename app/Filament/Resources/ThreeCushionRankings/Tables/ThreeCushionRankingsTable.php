<?php

namespace App\Filament\Resources\ThreeCushionRankings\Tables;

use App\Models\Category;
use App\Models\ThreeCushionRanking;
use App\Services\ThreeCushionRankingService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ThreeCushionRankingsTable
{
    public static function configure(Table $table): Table
    {
        $discipline = app(ThreeCushionRankingService::class)->discipline();
        $categoryOptions = Category::query()
            ->when($discipline, fn ($query) => $query->where('discipline_id', $discipline->id))
            ->orderBy('order')
            ->pluck('name', 'id')
            ->all();
        $defaultCategoryId = array_key_first($categoryOptions);

        return $table
            ->columns([
                TextColumn::make('position')->label('Pos.')->sortable(),
                TextColumn::make('player.full_name')->label('Apellido y Nombre')->searchable(['last_name', 'first_name']),
                TextColumn::make('player.club.name')->label('Club')->searchable()->wrap(),
                TextColumn::make('total_caroms')->label('Carambolas')->numeric()->sortable(),
                TextColumn::make('total_innings')->label('Entradas')->numeric()->sortable(),
                TextColumn::make('high_run')->label('Serie Mayor')->numeric()->sortable(),
                TextColumn::make('general_average')->label('Prom. General')->numeric(3)->sortable(),
                TextColumn::make('best_match_average')->label('Mejor Prom. Particular')->numeric(3)->sortable(),
                TextColumn::make('ranking_points')->label('Puntos Ranking')->numeric(2)->sortable(),
                TextColumn::make('participations')->label('Etapas')->state(
                    fn (ThreeCushionRanking $record): string => "{$record->stages_played} / {$record->total_stages}"
                ),
                ViewColumn::make('stage_details')
                    ->label('Detalle')
                    ->view('filament.tables.columns.three-cushion-stage-details'),
            ])
            ->filters([
                SelectFilter::make('season')->label('Año')->options(
                    ThreeCushionRanking::query()->distinct()->orderByDesc('season')->pluck('season', 'season')->all()
                )->default((string) now()->year),
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->options($categoryOptions)
                    ->default($defaultCategoryId === null ? null : (string) $defaultCategoryId)
                    ->selectablePlaceholder(false),
            ])
            ->defaultSort('position')
            ->paginated([25, 50, 100])
            ->recordActions([]);
    }
}
