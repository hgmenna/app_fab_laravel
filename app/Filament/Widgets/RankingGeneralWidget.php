<?php

namespace App\Filament\Widgets;

use App\Services\RankingService;
use Filament\Tables;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use App\Models\GeneralRanking;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;

class RankingGeneralWidget extends TableWidget
{
    protected static ?string $heading = 'Ranking Circuito Argentino de 5 Quillas';
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
            ->query(GeneralRanking::query())
            ->paginated([16, 32, 48])
            ->defaultPaginationPageOption(16)
            ->striped()
            ->extraAttributes(['class' => 'text-center'])

            ->headerActions([
                Action::make('descargarPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        // SOLUCIÓN TÉCNICA: Aumentar memoria y tiempo de ejecución
                        ini_set('memory_limit', '512M'); // Subimos a 512MB
                        set_time_limit(300);             // Damos 5 minutos de margen

                        // Se obtienen los registros respetando filtros y búsquedas aplicadas [1]
                        $records = $this->getFilteredTableQuery()->get();

                        $pdf = Pdf::loadView('pdf.ranking', [
                            'records' => $records,
                        ])->setPaper('a4', 'landscape'); 

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, 'ranking-actual.pdf');
                    }
                ),
            ])

            ->columns([
                ColumnGroup::make('Ranking', [
                    TextColumn::make('RG')
                        ->label('RG')
                        ->alignment('center')
                        ->limit(3)
                        ->width('7px'),
    
                    TextColumn::make('RC')
                        ->label('RC')
                        ->alignment('center')
                        ->limit(3)
                        ->width('7px')
                        ->extraCellAttributes(fn ($record) => $record['RC'] === 1
                            ? ['style' => 'background-color: #065f46 !important; color: #ffffff !important; font-weight: 700 !important; border-left: 1px solid #047857;',]
                            : []
                        ),

                ]),

                ColumnGroup::make('Datos Personales', [
                    TextColumn::make('last_name')
                        ->label('Apellido')
                        ->width('60px')
                        ->wrap()
                        ->alignment('center')
                        ->searchable(),
    
                    TextColumn::make('first_name')
                        ->label('N')
                        ->alignment('center')
                        ->limit(1, '')
                        ->width('5px'),
    
                    TextColumn::make('club')
                        ->label('Club')
                        ->alignment('center')
                        ->searchable()
                        ->width('150px')
                        ->wrap()
                        ->limit(20),
    
                    TextColumn::make('category')
                        ->label('Cat')
                        ->alignment('center')
                        ->limit(3)
                        ->width('7px'),

                    TextColumn::make('fed')
                        ->label('Fed')
                        ->alignment('center')
                        ->width('30px'),
    
                    TextColumn::make('total_puntos')
                        ->label('Tot')
                        ->alignment('center')
                        ->extraCellAttributes([
                             'style' => 'background-color: #065f46 !important; color: #ffffff !important; font-weight: 700 !important; border-left: 1px solid #047857;', ])// El "!" asegura que el color de fondo sobresalga
                        ->limit(3, '')
                        ->width('25px'),
                ]),

                    ColumnGroup::make('Etapa 1', [
                        TextColumn::make('pos_1')->label('Pos')->limit(7)->alignment('center')->width('20px'),
                        TextColumn::make('ptos_1')->label('Pts')->numeric(decimalPlaces:0)->alignment('center')->width('10px'),
                    ]),

                    ColumnGroup::make('Etapa 2', [
                        TextColumn::make('pos_2')->label('Pos')->limit(7)->alignment('center')->width('20px'),
                        TextColumn::make('ptos_2')->label('Pts')->numeric(decimalPlaces:0)->limit(2)->alignment('center')->width('10px'),
                    ]),

                    ColumnGroup::make('Etapa 3',[
                        TextColumn::make('pos_3')->label('Pos')->limit(7)->alignment('center')->width('20px'),
                        TextColumn::make('ptos_3')->label('Pts')->numeric(decimalPlaces:0)->limit(2)->alignment('center')->width('10px'),
                    ]),

                    ColumnGroup::make('Etapa 4', [
                        TextColumn::make('pos_4')->label('Pos')->limit(7)->alignment('center')->width('20px'),
                        TextColumn::make('ptos_4')->label('Pts')->numeric(decimalPlaces:0)->limit(2)->alignment('center')->width('10px'),
                    ]),

            ])
            ->filters ([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'M' => 'Master',
                        'N' => 'Nacional',
                        'P' => 'Primera',
                        'S' => 'Segunda',
                        'T' => 'Tercera',
                        'PR' => 'Promocional'
                    ]),
            ])
             /**
             * MODIFICACIÓN: Extraemos el estado de los filtros y la búsqueda 
             * directamente de las propiedades del componente Livewire.
             **/
            ;
    }
}
