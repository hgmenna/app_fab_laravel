<?php

namespace App\Filament\Resources\MembershipAccounts\Pages;

use App\Filament\Resources\MembershipAccounts\MembershipAccountResource;
use App\Filament\Resources\Players\PlayerResource;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\Federation;
use App\Models\Membership;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\URL;

class ListMembershipAccounts extends ListRecords
{
    protected static string $resource = MembershipAccountResource::class;
    protected static ?string $title = 'Cuenta corriente de membresías';

    protected function getHeaderActions(): array
    {
        return [
            PlayerResource::renewClubMembershipsAction(),
            Action::make('printEntityStatement')
                ->label('Imprimir estado consolidado')
                ->icon('heroicon-o-printer')
                ->iconButton()
                ->tooltip('Exportar o imprimir la cuenta corriente de un club o federación')
                ->modalHeading('Estado de cuenta consolidado')
                ->modalDescription('Seleccioná la entidad, disciplina y año que querés incluir en el PDF.')
                ->schema($this->entityStatementSchema())
                ->modalSubmitActionLabel('Generar PDF')
                ->action(fn (array $data) => redirect()->away($this->entityStatementUrl($data))),
            Action::make('shareEntityStatement')
                ->label('Compartir estado consolidado')
                ->icon('heroicon-o-share')
                ->iconButton()
                ->color('success')
                ->tooltip('Compartir la cuenta corriente de un club o federación por WhatsApp')
                ->modalHeading('Compartir estado de cuenta')
                ->modalDescription('Seleccioná la entidad, disciplina y año. Se generará un enlace al PDF.')
                ->schema($this->entityStatementSchema())
                ->modalSubmitActionLabel('Abrir WhatsApp')
                ->action(function (array $data) {
                    $entity = $data['entity_type'] === 'club'
                        ? Club::find($data['entity_id'])
                        : Federation::find($data['entity_id']);
                    $discipline = Discipline::find($data['discipline_id']);
                    $message = "Estado de cuenta de membresías\n"
                        .($entity?->name ?? 'Entidad')."\n"
                        .($discipline?->name ?? 'Disciplina').' · '.$data['year']."\n"
                        .$this->entityStatementUrl($data);

                    return redirect()->away('https://wa.me/?text='.rawurlencode($message));
                }),
        ];
    }

    private function entityStatementSchema(): array
    {
        return [
            Select::make('entity_type')
                ->label('Tipo de entidad')
                ->options(['club' => 'Club / sala', 'federation' => 'Federación'])
                ->required()
                ->live()
                ->afterStateUpdated(fn ($set) => $set('entity_id', null)),
            Select::make('entity_id')
                ->label(fn (Get $get): string => $get('entity_type') === 'federation' ? 'Federación' : 'Club / sala')
                ->options(fn (Get $get): array => match ($get('entity_type')) {
                    'club' => Club::query()->orderBy('name')->pluck('name', 'id')->all(),
                    'federation' => Federation::query()->orderBy('name')->pluck('name', 'id')->all(),
                    default => [],
                })
                ->searchable()
                ->preload()
                ->required(),
            Select::make('discipline_id')
                ->label('Disciplina')
                ->options(fn (): array => Discipline::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->required(),
            Select::make('year')
                ->label('Año')
                ->options(fn (): array => Membership::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all())
                ->default((int) now()->year)
                ->required(),
        ];
    }

    private function entityStatementUrl(array $data): string
    {
        return URL::temporarySignedRoute(
            'membership-entity-statements.pdf',
            now()->addDays(7),
            $data,
        );
    }
}
