<?php

namespace App\Filament\Resources\DisciplineModalities;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Resources\DisciplineModalities\Pages\CreateDisciplineModality;
use App\Filament\Resources\DisciplineModalities\Pages\EditDisciplineModality;
use App\Filament\Resources\DisciplineModalities\Pages\ListDisciplineModalities;
use App\Models\DisciplineModality;
use App\Services\AdminNotifier;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DisciplineModalityResource extends Resource
{
    protected static ?string $model = DisciplineModality::class;

    protected static string|UnitEnum|null $navigationGroup = 'Gestión Deportiva';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Modalidades';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('discipline_id')
                ->relationship('discipline', 'name')
                ->label('Disciplina')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('name')->label('Modalidad')->required()->maxLength(255),
            TextInput::make('code')->label('Código')->required()->maxLength(50),
            TextInput::make('players_per_registration')
                ->label('Jugadores por inscripción')
                ->helperText('Cantidad exacta de integrantes de cada inscripción. El valor inicial es 1.')
                ->numeric()->minValue(1)->default(1)->required(),
            TextInput::make('order')->label('Orden')->numeric()->default(0)->required(),
            Toggle::make('is_active')->label('Activa')->default(true)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('discipline.name')->label('Disciplina')->searchable()->sortable(),
            TextColumn::make('name')->label('Modalidad')->searchable()->sortable(),
            TextColumn::make('code')->label('Código')->searchable(),
            TextColumn::make('players_per_registration')->label('Integrantes')->alignCenter(),
            TextColumn::make('order')->label('Orden')->sortable(),
            IconColumn::make('is_active')->label('Activa')->boolean(),
            TextColumn::make('tournament_modalities_count')->label('Torneos')->counts('tournamentModalities'),
        ])->defaultSort('order')->filters([
            SelectFilter::make('discipline_id')->relationship('discipline', 'name')->label('Disciplina'),
        ])->recordActions([
            GlobalActionGroup::make([
                GlobalEditAction::make(),
                AdminNotifier::notifyAction(GlobalDeleteAction::make(), null, 'eliminó', ['name'], 'Modalidades'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisciplineModalities::route('/'),
            'create' => CreateDisciplineModality::route('/create'),
            'edit' => EditDisciplineModality::route('/{record}/edit'),
        ];
    }
}
