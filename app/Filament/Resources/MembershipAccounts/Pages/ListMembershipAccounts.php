<?php

namespace App\Filament\Resources\MembershipAccounts\Pages;

use App\Filament\Resources\MembershipAccounts\MembershipAccountResource;
use App\Filament\Resources\Players\PlayerResource;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\Federation;
use App\Models\Membership;
use App\Models\PlayerMembership;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListMembershipAccounts extends ListRecords
{
    protected static string $resource = MembershipAccountResource::class;
    protected static ?string $title = 'Cuenta corriente de membresías';

    protected function getHeaderActions(): array
    {
        return [
            PlayerResource::renewClubMembershipsAction(),
            Action::make('recordInstitutionalPayment')
                ->label('Registrar pago institucional')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->tooltip('Registrar un pago parcial o total de un club o federación')
                ->modalHeading('Registrar pago institucional')
                ->modalDescription('El pago se aplica al saldo global. Puede repetir esta operación hasta cancelar la deuda.')
                ->schema([
                    ...$this->entityStatementSchema(),
                    TextInput::make('amount')->label('Importe recibido')->numeric()->prefix('$')->minValue(0.01)->required(),
                    Select::make('manual_method')->label('Medio de pago')->options([
                        'bank_transfer' => 'Transferencia bancaria', 'cash' => 'Efectivo', 'other' => 'Otro medio',
                    ])->required(),
                    TextInput::make('reference')->label('Referencia / número de operación'),
                    Textarea::make('notes')->label('Observación')->columnSpanFull(),
                ])
                ->modalSubmitActionLabel('Registrar pago')
                ->action(function (array $data): void {
                    $accounts = $this->entityAccounts($data)->filter(fn (PlayerMembership $account): bool => $account->balance > 0);
                    $balance = $accounts->sum(fn (PlayerMembership $account): float => $account->balance);
                    $amount = (float) $data['amount'];
                    if ($accounts->isEmpty()) {
                        throw ValidationException::withMessages(['amount' => 'La entidad no tiene saldo pendiente para los filtros seleccionados.']);
                    }
                    if ($amount > $balance) {
                        throw ValidationException::withMessages(['amount' => 'El importe supera el saldo pendiente de $ '.number_format($balance, 2, ',', '.')]);
                    }
                    $payerType = $data['entity_type'];
                    $payerId = (int) $data['entity_id'];
                    DB::transaction(function () use ($accounts, $amount, $data, $payerType, $payerId): void {
                        $remaining = $amount;
                        foreach ($accounts as $account) {
                            if ($remaining <= 0) break;
                            $allocated = min($remaining, $account->balance);
                            $account->payments()->create([
                                'payer_type' => $payerType, 'payer_id' => $payerId, 'amount' => $allocated,
                                'method' => 'manual', 'manual_method' => $data['manual_method'],
                                'external_reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
                                'status' => 'approved', 'recorded_by' => Auth::id(), 'paid_at' => now(),
                            ]);
                            $newPaid = (float) $account->amount_paid + $allocated;
                            $paid = $newPaid >= (float) $account->amount_due;
                            $account->update([
                                'amount_paid' => $newPaid, 'status' => $paid ? 'paid' : 'partial',
                                'enabled_to_compete' => true, 'paid_at' => $paid ? now() : null,
                            ]);
                            $account->player?->update(['is_active' => true, 'is_enabled_to_compete' => true]);
                            $remaining -= $allocated;
                        }
                    });
                    Notification::make()->success()->title('Pago institucional registrado')
                        ->body('Importe acreditado: $ '.number_format($amount, 2, ',', '.').'. Saldo restante: $ '.number_format($balance - $amount, 2, ',', '.'))->send();
                }),
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

    private function entityAccounts(array $data)
    {
        $discipline = Discipline::with('directFederation')->findOrFail($data['discipline_id']);
        return PlayerMembership::query()
            ->with(['player.club.city.state', 'membership'])
            ->whereHas('membership', fn ($query) => $query->where('discipline_id', $discipline->id)->where('year', $data['year']))
            ->where('debtor_type', $data['entity_type'])
            ->where('debtor_id', $data['entity_id'])
            ->orderBy('id')->get();
    }
}
