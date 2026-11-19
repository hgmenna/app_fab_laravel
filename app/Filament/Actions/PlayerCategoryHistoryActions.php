<?php

namespace App\Filament\Actions;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\Category;
use App\Models\Player;
use App\Models\PlayerCategoryHistory;
use App\Services\PlayerCategoryHistoryEditor;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Gate;

/** Acciones compartidas por el informe de cambios y la ficha del jugador. Nunca borran el historial en bruto. */
class PlayerCategoryHistoryActions
{
    public static function create(?Player $owner = null): Action
    {
        return Action::make('createCategoryChange')->label('Nuevo cambio de categoría')->icon('heroicon-o-plus')
            ->visible(fn() => Gate::allows('create', PlayerCategoryHistory::class))
            ->schema(static::fields($owner))
            ->action(function (array $data) use ($owner) {
                $player = $owner ?? PlayerResource::getEloquentQuery()->findOrFail($data['player_id']);
                app(PlayerCategoryHistoryEditor::class)->create($player, $data);
                Notification::make()->success()->title('Cambio de categoría guardado')->send();
            });
    }

    public static function edit(): Action
    {
        return Action::make('editCategoryChange')->label('Editar')->icon('heroicon-o-pencil-square')
            ->visible(fn(PlayerCategoryHistory $record) => Gate::allows('update', $record))
            ->fillForm(fn(PlayerCategoryHistory $record) => [
                'player_id' => $record->player_id, 'category_id' => $record->category_id,
                'effective_date' => $record->effective_date?->format('Y-m-d'), 'reason' => $record->reason, 'notes' => $record->notes,
            ])->schema(static::fields())
            ->modalDescription('La corrección recompone la categoría actual y la secuencia de cambios permanentes. Los resultados históricos de torneos no se modifican.')
            ->action(function (PlayerCategoryHistory $record, array $data) {
                app(PlayerCategoryHistoryEditor::class)->update($record, $data);
                Notification::make()->success()->title('Cambio y categoría del jugador actualizados')->send();
            });
    }

    public static function delete(): Action
    {
        return Action::make('deleteCategoryChange')->label('Eliminar')->icon('heroicon-o-trash')->color('danger')
            ->visible(fn(PlayerCategoryHistory $record) => Gate::allows('delete', $record))
            ->requiresConfirmation()->modalHeading('Eliminar cambio de categoría')
            ->modalDescription('Se eliminará este cambio y se recalculará la categoría vigente del jugador con los cambios restantes.')
            ->action(function (PlayerCategoryHistory $record) {
                app(PlayerCategoryHistoryEditor::class)->delete($record);
                Notification::make()->success()->title('Cambio eliminado y categoría recalculada')->send();
            });
    }

    public static function view(): ViewAction
    {
        return ViewAction::make()->label('Ver')->visible(fn(PlayerCategoryHistory $record) => Gate::allows('view', $record))
            ->schema([
                Placeholder::make('player')->label('Jugador')->content(fn($record) => $record->player?->full_name),
                Placeholder::make('previous')->label('Anterior')->content(fn($record) => $record->previousCategory?->name),
                Placeholder::make('new')->label('Nueva')->content(fn($record) => $record->category?->name),
                DatePicker::make('effective_date')->label('Fecha efectiva'),
                Textarea::make('reason')->label('Motivo'), Textarea::make('notes')->label('Observaciones'),
            ]);
    }

    private static function fields(?Player $owner = null): array
    {
        return [
            Select::make('player_id')->label('Jugador')->searchable()->required()->default($owner?->id)
                ->hidden(fn(?PlayerCategoryHistory $record) => $owner !== null || $record !== null)
                ->getSearchResultsUsing(fn(string $search) => PlayerResource::getEloquentQuery()
                    ->where(fn($query) => $query->where('last_name', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%'))
                    ->limit(50)->get()->mapWithKeys(fn($player) => [$player->id => $player->full_name])->all())
                ->getOptionLabelUsing(fn($value) => PlayerResource::getEloquentQuery()->find($value)?->full_name)
                ->live()->afterStateUpdated(fn(Set $set) => $set('category_id', null)),
            Placeholder::make('player_name')->label('Jugador')->content(fn(?PlayerCategoryHistory $record) => $record?->player?->full_name ?? $owner?->full_name)
                ->visible(fn(?PlayerCategoryHistory $record) => $owner !== null || $record !== null),
            Select::make('category_id')->label('Nueva categoría')->required()->searchable()
                ->options(function (Get $get, ?PlayerCategoryHistory $record) use ($owner) {
                    $player = $owner ?? $record?->player ?? PlayerResource::getEloquentQuery()->find($get('player_id'));
                    return $player ? Category::query()->where('discipline_id', $player->discipline_id)
                        ->whereNotIn('code', config('ranking.temporary_ranking_categories', ['M', 'N']))
                        ->orderBy('order')->orderBy('name')->pluck('name', 'id')->all() : [];
                }),
            DatePicker::make('effective_date')->label('Fecha efectiva')->required()->default(today())->native(false),
            Textarea::make('reason')->label('Motivo')->required()->maxLength(500),
            Textarea::make('notes')->label('Observaciones')->maxLength(1000),
        ];
    }
}
