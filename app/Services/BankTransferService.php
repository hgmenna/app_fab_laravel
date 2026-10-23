<?php

namespace App\Services;

use App\Models\BankTransferOrder;
use App\Models\BankTransferSetting;
use App\Models\Club;
use App\Models\Membership;
use App\Models\Player;
use App\Models\PlayerMembership;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Notifications\SystemActivityEmailNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Gestiona el ciclo de órdenes de transferencia para afiliaciones e inscripciones.
 */
class BankTransferService
{
    /**
     * Crea una orden de transferencia para una o varias renovaciones.
     */
    public function createMembershipOrder(
        Membership $membership,
        Collection $players,
        ?Club $club,
        string $payerType = 'players',
        ?int $payerId = null,
        ?string $payerName = null,
        bool $preservePlayerClub = false,
        ?float $amountPerPlayer = null,
    ): BankTransferOrder
    {
        $setting = $this->requireActiveSetting();

        return DB::transaction(function () use ($membership, $players, $club, $setting, $payerType, $payerId, $payerName, $preservePlayerClub, $amountPerPlayer): BankTransferOrder {
            $renewals = $players->map(function (Player $player) use ($membership, $club, $preservePlayerClub, $amountPerPlayer): PlayerMembership {
                if ((int) $player->discipline_id !== (int) $membership->discipline_id) {
                    throw ValidationException::withMessages([
                        'player_ids' => "{$player->full_name} no pertenece a la disciplina seleccionada.",
                    ]);
                }

                $playerMembership = PlayerMembership::query()->firstOrNew([
                    'player_id' => $player->id,
                    'membership_id' => $membership->id,
                ]);

                if ($playerMembership->status === 'paid') {
                    throw ValidationException::withMessages([
                        'player_ids' => "{$player->full_name} ya tiene la membresía pagada.",
                    ]);
                }

                $currentPaid = (float) ($playerMembership->amount_paid ?: 0);
                $balance = max(0, (float) $membership->amount - $currentPaid);
                $paymentAmount = $amountPerPlayer ?? $balance;
                if ($paymentAmount <= 0 || $paymentAmount > $balance) {
                    throw ValidationException::withMessages([
                        'payment_amount' => "El importe indicado no es válido para {$player->full_name}. Saldo: $ ".number_format($balance, 2, ',', '.'),
                    ]);
                }

                $playerMembership->fill([
                    'club_id' => $preservePlayerClub ? $player->club_id : $club?->id,
                    'amount_due' => $membership->amount,
                    'amount_paid' => $currentPaid,
                    'status' => $currentPaid > 0 ? 'partial' : 'pending',
                    'enabled_to_compete' => false,
                    'paid_at' => null,
                ])->save();
                $playerMembership->setAttribute('payment_amount_requested', $paymentAmount);

                return $playerMembership;
            });

            $this->guardAgainstOpenOrders($renewals);

            $order = BankTransferOrder::query()->create([
                'code' => $this->nextCode(),
                'user_id' => Auth::id(),
                'club_id' => $club?->id,
                'discipline_id' => $membership->discipline_id,
                'purpose' => 'membership',
                'amount' => $renewals->sum(fn (PlayerMembership $renewal): float => (float) $renewal->payment_amount_requested),
                'status' => 'pending',
                'expires_at' => today()->addDays($setting->expires_in_days),
                'payer_type' => $payerType,
                'payer_id' => $payerId,
                'payer_name' => $payerName ?: ($payerType === 'club' ? ($club?->name ?? 'Club') : 'Jugadores seleccionados'),
                ...$this->destinationSnapshot($setting, 'federation', 'Federación Argentina de Billar'),
            ]);

            foreach ($renewals as $renewal) {
                $order->items()->create([
                    'payable_type' => $renewal->getMorphClass(),
                    'payable_id' => $renewal->getKey(),
                    'description' => "Membresía {$membership->year} — {$renewal->player->full_name}",
                    'amount' => $renewal->payment_amount_requested,
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Crea, si corresponde, la orden de pago de una inscripción.
     */
    public function createTournamentRegistrationOrder(TournamentRegistration $registration): ?BankTransferOrder
    {
        $registration->loadMissing(['tournament.organizerClub', 'tournament.contacts', 'participants.player.category']);

        if (! $registration->tournament?->is_payment_enabled) {
            $registration->updateQuietly(['payment_status' => 'paid']);

            return null;
        }

        if ($registration->bankTransferOrderItems()->whereHas('order', fn ($query) => $query
            ->whereIn('status', ['pending', 'submitted', 'observed']))->exists()) {
            return $registration->bankTransferOrderItems()->whereHas('order', fn ($query) => $query
                ->whereIn('status', ['pending', 'submitted', 'observed']))->first()?->order;
        }

        $globalSetting = BankTransferSetting::active();
        $organizer = $registration->tournament->organizerClub;
        if ($organizer && ! $organizer->hasBankTransferData()) {
            throw ValidationException::withMessages([
                'payment' => "El club organizador {$organizer->name} debe completar sus datos bancarios antes de recibir inscripciones pagas.",
            ]);
        }
        $destination = $organizer ?: ($globalSetting ?? throw ValidationException::withMessages([
            'payment' => 'El torneo no tiene club organizador y la cuenta bancaria de la Federación no está configurada.',
        ]));
        $expiresInDays = (int) ($globalSetting?->expires_in_days ?: 7);
        $amount = $this->registrationAmount($registration);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'price' => 'El torneo requiere pago pero no tiene un importe configurado para esta inscripción.',
            ]);
        }

        return DB::transaction(function () use ($registration, $destination, $organizer, $expiresInDays, $amount): BankTransferOrder {
            $registration->updateQuietly(['price' => $amount, 'payment_status' => 'pending']);

            $registrationIds = TournamentRegistration::query()
                ->where('tournament_id', $registration->tournament_id)
                ->select('id');
            $order = BankTransferOrder::query()
                ->where('user_id', Auth::id())
                ->where('purpose', 'tournament_registration')
                ->where('status', 'pending')
                ->where('club_id', $organizer?->id)
                ->whereNull('receipt_path')
                ->whereHas('items', fn ($items) => $items
                    ->where('payable_type', $registration->getMorphClass())
                    ->whereIn('payable_id', $registrationIds))
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($order) {
                $order->items()->create([
                    'payable_type' => $registration->getMorphClass(),
                    'payable_id' => $registration->getKey(),
                    'description' => "Inscripción — {$registration->tournament->name} — {$registration->participant_names}",
                    'amount' => $amount,
                ]);
                $order->update([
                    'amount' => $order->items()->sum('amount'),
                    'expires_at' => today()->addDays($expiresInDays),
                ]);

                return $order->refresh()->load('items');
            }

            $order = BankTransferOrder::query()->create([
                'code' => $this->nextCode(),
                'user_id' => Auth::id(),
                'club_id' => $organizer?->id,
                'discipline_id' => $registration->tournament->discipline_id,
                'purpose' => 'tournament_registration',
                'amount' => $amount,
                'status' => 'pending',
                'expires_at' => today()->addDays($expiresInDays),
                ...$this->destinationSnapshot(
                    $destination,
                    $organizer ? 'club' : 'federation',
                    $organizer?->name ?: 'Federación Argentina de Billar',
                ),
            ]);
            $order->items()->create([
                'payable_type' => $registration->getMorphClass(),
                'payable_id' => $registration->getKey(),
                'description' => "Inscripción — {$registration->tournament->name} — {$registration->participant_names}",
                'amount' => $amount,
            ]);

            return $order->load('items');
        });
    }

    /**
     * Presenta la orden y su comprobante para revisión.
     */
    public function submit(BankTransferOrder $order): void
    {
        if (! in_array($order->status, ['pending', 'observed'], true) || blank($order->receipt_path)) {
            throw ValidationException::withMessages(['receipt_path' => 'Adjuntá el comprobante antes de enviarlo a revisión.']);
        }

        $order->update(['status' => 'submitted', 'submitted_at' => now()]);
        $this->notifyParticipants($order, 'Comprobante recibido', "La orden {$order->code} fue enviada a Tesorería para su revisión.");
    }

    /**
     * Aprueba la transferencia y aplica el pago a su concepto de origen.
     */
    public function approve(BankTransferOrder $order): void
    {
        if (! in_array($order->status, ['submitted', 'observed'], true)) {
            throw ValidationException::withMessages(['status' => 'La orden no está pendiente de revisión.']);
        }
        if ((float) $order->transferred_amount < (float) $order->amount) {
            throw ValidationException::withMessages(['transferred_amount' => 'El importe verificado es menor que el total de la orden.']);
        }

        $memberships = collect();
        DB::transaction(function () use ($order, $memberships): void {
            $order->load('items.payable');
            foreach ($order->items as $item) {
                $payable = $item->payable;
                if ($payable instanceof PlayerMembership) {
                    $newPaid = min((float) $payable->amount_due, (float) $payable->amount_paid + (float) $item->amount);
                    $fullyPaid = $newPaid >= (float) $payable->amount_due;
                    $payable->update([
                        'amount_paid' => $newPaid,
                        'status' => $fullyPaid ? 'paid' : 'partial',
                        'enabled_to_compete' => $fullyPaid,
                        'paid_at' => $fullyPaid ? now() : null,
                    ]);
                    $payable->payments()->updateOrCreate(
                        ['external_reference' => $order->code],
                        [
                            'payer_type' => in_array($order->payer_type, ['club', 'federation'], true) ? $order->payer_type : 'player',
                            'payer_id' => in_array($order->payer_type, ['club', 'federation'], true) ? $order->payer_id : $payable->player_id,
                            'amount' => $item->amount,
                            'method' => 'manual',
                            'status' => 'approved',
                            'paid_at' => now(),
                            'recorded_by' => Auth::id(),
                        ],
                    );
                    $playerData = ['club_id' => $payable->club_id, 'is_active' => true];
                    if ($fullyPaid) {
                        $playerData['is_enabled_to_compete'] = true;
                    }
                    $payable->player()->update($playerData);
                    $payable->setAttribute('operation_label', $fullyPaid ? 'Pago total' : 'Pago parcial');
                    $memberships->push($payable);
                }
                if ($payable instanceof TournamentRegistration) {
                    $payable->update(['payment_status' => 'paid']);
                }
            }
            $order->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        $memberships->groupBy('membership_id')->each(function (Collection $renewals) use ($order): void {
            $membership = $renewals->first()?->membership;
            if ($membership) {
                app(AnnualMembershipService::class)->sendConfirmation($membership, $renewals, $order->user);
            }
        });
        $this->notifyParticipants($order, 'Transferencia aprobada', "Tesorería aprobó la orden {$order->code}. Los conceptos asociados quedaron acreditados.");
    }

    /**
     * Rechaza la transferencia conservando el motivo para auditoría.
     */
    public function reject(BankTransferOrder $order, string $notes): void
    {
        $order->update([
            'status' => 'rejected', 'review_notes' => $notes,
            'reviewed_by' => Auth::id(), 'reviewed_at' => now(),
        ]);
        $this->notifyParticipants($order, 'Transferencia rechazada', "Tesorería rechazó la orden {$order->code}. Motivo: {$notes}");
    }

    /**
     * Elimina una orden únicamente cuando su estado permite descartarla.
     */
    public function deleteOrder(BankTransferOrder $order): void
    {
        if ($order->status === 'approved') {
            throw ValidationException::withMessages([
                'status' => 'Una transferencia aprobada no puede eliminarse. Debe realizarse una reversión administrativa.',
            ]);
        }

        DB::transaction(function () use ($order): void {
            $order->load('items.payable');

            foreach ($order->items as $item) {
                if ($item->payable instanceof TournamentRegistration) {
                    $item->payable->updateQuietly(['payment_status' => 'pending']);
                }
            }

            $receiptPath = $order->receipt_path;
            $order->delete();

            if (filled($receiptPath)) {
                Storage::disk('public_path')->delete($receiptPath);
            }
        });
    }

    /**
     * Obtiene la configuración bancaria activa o informa que no está disponible.
     */
    private function requireActiveSetting(): BankTransferSetting
    {
        return BankTransferSetting::active() ?? throw ValidationException::withMessages([
            'payment' => 'Configurá primero la cuenta bancaria receptora en Configuración General.',
        ]);
    }

    /**
     * Captura los datos bancarios de destino para conservar el valor histórico.
     */
    private function destinationSnapshot(BankTransferSetting|Club $source, string $type, string $name): array
    {
        $club = $source instanceof Club;

        return [
            'beneficiary_type' => $type,
            'beneficiary_name' => $name,
            'destination_account_holder' => $club ? $source->bank_account_holder : $source->account_holder,
            'destination_tax_id' => $club ? $source->bank_tax_id : $source->tax_id,
            'destination_bank_name' => $club ? $source->bank_name : $source->bank_name,
            'destination_account_type' => $club ? $source->bank_account_type : $source->account_type,
            'destination_account_number' => $club ? $source->bank_account_number : $source->account_number,
            'destination_cbu' => $club ? $source->bank_cbu : $source->cbu,
            'destination_alias' => $club ? $source->bank_alias : $source->alias,
            'destination_treasury_email' => $club ? $source->bank_treasury_email : $source->treasury_email,
            'destination_instructions' => $club ? $source->bank_transfer_instructions : $source->instructions,
        ];
    }

    /**
     * Impide crear órdenes duplicadas para obligaciones con pagos abiertos.
     */
    private function guardAgainstOpenOrders(Collection $renewals): void
    {
        $ids = $renewals->pluck('id');
        $exists = \App\Models\BankTransferOrderItem::query()
            ->where('payable_type', (new PlayerMembership)->getMorphClass())
            ->whereIn('payable_id', $ids)
            ->whereHas('order', fn ($query) => $query->whereIn('status', ['pending', 'submitted', 'observed']))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['player_ids' => 'Uno o más jugadores ya tienen una orden de pago pendiente.']);
        }
    }

    /**
     * Calcula el importe exigible para una inscripción.
     */
    private function registrationAmount(TournamentRegistration $registration): float
    {
        if ((float) $registration->price > 0) {
            return (float) $registration->price;
        }
        $categoryId = $registration->participants->first()?->player?->category_id;
        $price = $registration->tournament->categoryPrices()
            ->where('tournament_modality_id', $registration->tournament_modality_id)
            ->where('category_id', $categoryId)
            ->value('price');

        return (float) ($price ?: $registration->tournament->entry_fee ?: 0);
    }

    /**
     * Genera el próximo código público de orden.
     */
    private function nextCode(): string
    {
        return 'TR-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(8));
    }

    /**
     * Envía una notificación a los participantes vinculados con la orden.
     */
    private function notifyParticipants(BankTransferOrder $order, string $title, string $message): void
    {
        $recipients = User::query()
            ->whereKey($order->user_id)
            ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'super-admin'))
            ->get()
            ->unique('id')
            ->values();

        \Filament\Notifications\Notification::make()
            ->title($title)
            ->body($message)
            ->info()
            ->sendToDatabase($recipients);

        try {
            \Illuminate\Support\Facades\Notification::send(
                $recipients->filter(fn (User $user): bool => filled($user->email)),
                new SystemActivityEmailNotification($title, $message),
            );
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('No se pudo notificar una orden de transferencia.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
