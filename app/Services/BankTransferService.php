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

class BankTransferService
{
    public function createMembershipOrder(Membership $membership, Collection $players, ?Club $club): BankTransferOrder
    {
        $setting = $this->requireActiveSetting();

        return DB::transaction(function () use ($membership, $players, $club, $setting): BankTransferOrder {
            $renewals = $players->map(function (Player $player) use ($membership, $club): PlayerMembership {
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

                $playerMembership->fill([
                    'club_id' => $club?->id,
                    'amount_due' => $membership->amount,
                    'amount_paid' => 0,
                    'status' => 'pending',
                    'enabled_to_compete' => false,
                    'paid_at' => null,
                ])->save();

                return $playerMembership;
            });

            $this->guardAgainstOpenOrders($renewals);

            $order = BankTransferOrder::query()->create([
                'code' => $this->nextCode(),
                'user_id' => Auth::id(),
                'club_id' => $club?->id,
                'discipline_id' => $membership->discipline_id,
                'purpose' => 'membership',
                'amount' => $renewals->sum(fn (PlayerMembership $renewal): float => (float) $renewal->amount_due),
                'status' => 'pending',
                'expires_at' => today()->addDays($setting->expires_in_days),
            ]);

            foreach ($renewals as $renewal) {
                $order->items()->create([
                    'payable_type' => $renewal->getMorphClass(),
                    'payable_id' => $renewal->getKey(),
                    'description' => "Membresía {$membership->year} — {$renewal->player->full_name}",
                    'amount' => $renewal->amount_due,
                ]);
            }

            return $order->load('items');
        });
    }

    public function createTournamentRegistrationOrder(TournamentRegistration $registration): ?BankTransferOrder
    {
        $registration->loadMissing(['tournament', 'participants.player.category']);

        if (! $registration->tournament?->is_payment_enabled) {
            $registration->updateQuietly(['payment_status' => 'paid']);

            return null;
        }

        if ($registration->bankTransferOrderItems()->whereHas('order', fn ($query) => $query
            ->whereIn('status', ['pending', 'submitted', 'observed']))->exists()) {
            return $registration->bankTransferOrderItems()->whereHas('order', fn ($query) => $query
                ->whereIn('status', ['pending', 'submitted', 'observed']))->first()?->order;
        }

        $setting = $this->requireActiveSetting();
        $amount = $this->registrationAmount($registration);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'price' => 'El torneo requiere pago pero no tiene un importe configurado para esta inscripción.',
            ]);
        }

        return DB::transaction(function () use ($registration, $setting, $amount): BankTransferOrder {
            $registration->updateQuietly(['price' => $amount, 'payment_status' => 'pending']);

            $registrationIds = TournamentRegistration::query()
                ->where('tournament_id', $registration->tournament_id)
                ->select('id');
            $order = BankTransferOrder::query()
                ->where('user_id', Auth::id())
                ->where('purpose', 'tournament_registration')
                ->where('status', 'pending')
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
                    'expires_at' => today()->addDays($setting->expires_in_days),
                ]);

                return $order->refresh()->load('items');
            }

            $order = BankTransferOrder::query()->create([
                'code' => $this->nextCode(),
                'user_id' => Auth::id(),
                'club_id' => $registration->participants->first()?->player?->club_id,
                'discipline_id' => $registration->tournament->discipline_id,
                'purpose' => 'tournament_registration',
                'amount' => $amount,
                'status' => 'pending',
                'expires_at' => today()->addDays($setting->expires_in_days),
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

    public function submit(BankTransferOrder $order): void
    {
        if (! in_array($order->status, ['pending', 'observed'], true) || blank($order->receipt_path)) {
            throw ValidationException::withMessages(['receipt_path' => 'Adjuntá el comprobante antes de enviarlo a revisión.']);
        }

        $order->update(['status' => 'submitted', 'submitted_at' => now()]);
        $this->notifyParticipants($order, 'Comprobante recibido', "La orden {$order->code} fue enviada a Tesorería para su revisión.");
    }

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
                    $payable->update([
                        'amount_paid' => $item->amount,
                        'status' => 'paid',
                        'enabled_to_compete' => true,
                        'paid_at' => now(),
                    ]);
                    $payable->payments()->updateOrCreate(
                        ['external_reference' => $order->code],
                        [
                            'payer_type' => $order->club_id ? 'club' : 'player',
                            'payer_id' => $order->club_id ?: $payable->player_id,
                            'amount' => $item->amount,
                            'method' => 'manual',
                            'status' => 'approved',
                        ],
                    );
                    $payable->player()->update([
                        'club_id' => $payable->club_id,
                        'is_active' => true,
                        'is_enabled_to_compete' => true,
                    ]);
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
                $renewals->each(fn (PlayerMembership $renewal) => $renewal->setAttribute('operation_label', 'Renovación'));
                app(AnnualMembershipService::class)->sendConfirmation($membership, $renewals, $order->user);
            }
        });
        $this->notifyParticipants($order, 'Transferencia aprobada', "Tesorería aprobó la orden {$order->code}. Los conceptos asociados quedaron acreditados.");
    }

    public function reject(BankTransferOrder $order, string $notes): void
    {
        $order->update([
            'status' => 'rejected', 'review_notes' => $notes,
            'reviewed_by' => Auth::id(), 'reviewed_at' => now(),
        ]);
        $this->notifyParticipants($order, 'Transferencia rechazada', "Tesorería rechazó la orden {$order->code}. Motivo: {$notes}");
    }

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

    private function requireActiveSetting(): BankTransferSetting
    {
        return BankTransferSetting::active() ?? throw ValidationException::withMessages([
            'payment' => 'Configurá primero la cuenta bancaria receptora en Configuración General.',
        ]);
    }

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

    private function nextCode(): string
    {
        return 'TR-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(8));
    }

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
