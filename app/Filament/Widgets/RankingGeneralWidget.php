<?php

namespace App\Filament\Widgets;

use App\Services\RankingService;
use Filament\Tables;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;

class RankingGeneralWidget extends TableWidget
{
    protected static ?string $heading = 'Ranking General (Últimos 4 torneos)';
    protected static ?int $sort = 1; // opcional: orden en el dashboard

    protected static ?string $maxHeight = '600px'; // ajustable 
    //protected static bool $isScrollable = false; // scroll interno
    protected int|string|array $columnSpan = 'full';

    /**
     * @return \Filament\Tables\Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->paginated(10)
            ->striped()
            ->extraAttributes(['class' => 'text-center'])

            ->columns([
                ColumnGroup::make('Ranking', [
                    TextColumn::make('RG')
                        ->label('RG')
                        ->alignment('center')
                        ->limit(3),
    
                    TextColumn::make('RC')
                        ->label('RC')
                        ->alignment('center')
                        ->limit(3),
                ]),

                ColumnGroup::make('Datos Personales', [
                    TextColumn::make('last_name')
                        ->label('Apellido')
                        ->limit(10)
                        ->alignment('center')
                        ->searchable(),
    
                    TextColumn::make('first_name')
                        ->label('N')
                        ->alignment('center')
                        ->limit(1),
    
                    TextColumn::make('club')
                        ->label('Club')
                        ->alignment('center')
                        ->searchable()
                        ->limit(15),
    
                    TextColumn::make('category')
                        ->label('Cat')
                        ->alignment('center')
                        ->limit(3),
    
                    TextColumn::make('total_puntos')
                        ->label('Tot')
                        ->alignment('center')
                        ->color('success')
                        ->limit(3),
                ]),

                    ColumnGroup::make('Etapa 1', [
                        TextColumn::make('pos_1')->label('Pos')->limit(7)->alignment('center'),
                        TextColumn::make('ptos_1')->label('Pts')->numeric(decimalPlaces:0)->alignment('center'),
                    ]),

                    ColumnGroup::make('Etapa 2', [
                        TextColumn::make('pos_2')->label('Pos')->limit(7)->alignment('center'),
                        TextColumn::make('ptos_2')->label('Pts')->numeric(decimalPlaces:0)->limit(2)->alignment('center'),
                    ]),

                    ColumnGroup::make('Etapa 3',[
                        TextColumn::make('pos_3')->label('Pos')->limit(7)->alignment('center'),
                        TextColumn::make('ptos_3')->label('Pts')->numeric(decimalPlaces:0)->limit(2)->alignment('center'),
                    ]),

                    ColumnGroup::make('Etapa 4', [
                        TextColumn::make('pos_4')->label('Pos')->limit(7)->alignment('center'),
                        TextColumn::make('ptos_4')->label('Pts')->numeric(decimalPlaces:0)->limit(2)->alignment('center'),
                    ]),

            ])
            /** @var \Illuminate\Support\Collection $records */
            ->records(fn () => RankingService::getGeneralRanking());
    }
}
