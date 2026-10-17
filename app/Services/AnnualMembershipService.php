<?php

namespace App\Services;

use App\Mail\MembershipRenewalConfirmation;
use App\Models\Club;
use App\Models\Membership;
use App\Models\Player;
use App\Models\PlayerMembership;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class AnnualMembershipService
{
    public function configure(int $year, int $disciplineId, float $amount, string $dueDate): Membership
    {
        return DB::transaction(function () use ($year, $disciplineId, $amount, $dueDate): Membership {
            $membership = Membership::query()->firstOrNew([
                'year' => $year,
                'discipline_id' => $disciplineId,
            ]);
            $membership->fill([
                'amount' => $amount,
                'due_date' => $dueDate,
                'active' => true,
            ])->save();

            return $membership;
        });
    }

    public function renew(
        Membership $membership,
        Collection $players,
        ?Club $club,
        string $payerType = 'players',
        ?int $payerId = null,
        ?string $payerName = null,
        bool $preservePlayerClub = false,
        ?float $amountPerPlayer = null,
    ): Collection
    {
        if (! $membership->active) {
            throw ValidationException::withMessages([
                'membership' => 'La membresía anual seleccionada no está activa.',
            ]);
        }

        $order = app(BankTransferService::class)->createMembershipOrder(
            $membership, $players, $club, $payerType, $payerId, $payerName, $preservePlayerClub, $amountPerPlayer
        );

        return $order->items->map(fn ($item) => $item->payable()->first())->filter()->values();
    }

    public function createInstitutionalDebt(
        Membership $membership,
        Collection $players,
        ?Club $club,
        bool $preservePlayerClub = false,
        string $debtorType = 'club',
        ?int $debtorId = null,
    ): Collection {
        return DB::transaction(function () use ($membership, $players, $club, $preservePlayerClub, $debtorType, $debtorId): Collection {
            return $players->map(function (Player $player) use ($membership, $club, $preservePlayerClub, $debtorType, $debtorId): PlayerMembership {
                $renewal = PlayerMembership::query()->firstOrNew([
                    'player_id' => $player->id,
                    'membership_id' => $membership->id,
                ]);
                $renewal->fill([
                    'club_id' => $preservePlayerClub ? $player->club_id : $club?->id,
                    'debtor_type' => $debtorType,
                    'debtor_id' => $debtorId,
                    'institutionally_guaranteed' => true,
                    'amount_due' => $membership->amount,
                    'amount_paid' => (float) ($renewal->amount_paid ?: 0),
                    'status' => (float) ($renewal->amount_paid ?: 0) > 0 ? 'partial' : 'pending',
                    'enabled_to_compete' => true,
                    'paid_at' => null,
                ])->save();
                $player->update([
                    'club_id' => $preservePlayerClub ? $player->club_id : $club?->id,
                    'is_active' => true,
                    'is_enabled_to_compete' => true,
                ]);

                return $renewal;
            });
        });
    }

    public function renewManually(
        Membership $membership,
        Collection $players,
        ?Club $club,
        string $payerType,
        ?int $payerId,
        string $manualMethod,
        float $amountPerPlayer,
        ?string $reference = null,
        ?string $notes = null,
        bool $preservePlayerClub = false,
    ): Collection {
        if (! $membership->active) {
            throw ValidationException::withMessages(['membership' => 'La membresía anual seleccionada no está activa.']);
        }

        if ($amountPerPlayer <= 0) {
            throw ValidationException::withMessages(['payment_amount' => 'El importe debe ser mayor que cero.']);
        }

        $renewals = DB::transaction(function () use ($membership, $players, $club, $payerType, $payerId, $manualMethod, $amountPerPlayer, $reference, $notes, $preservePlayerClub): Collection {
            return $players->map(function (Player $player) use ($membership, $club, $payerType, $payerId, $manualMethod, $amountPerPlayer, $reference, $notes, $preservePlayerClub): PlayerMembership {
                if ((int) $player->discipline_id !== (int) $membership->discipline_id) {
                    throw ValidationException::withMessages(['player_ids' => "{$player->full_name} no pertenece a la disciplina seleccionada."]);
                }

                $renewal = PlayerMembership::query()->firstOrNew([
                    'player_id' => $player->id, 'membership_id' => $membership->id,
                ]);
                if ($renewal->exists && $renewal->status === 'paid') {
                    throw ValidationException::withMessages(['player_ids' => "{$player->full_name} ya tiene la membresía pagada."]);
                }
                if ($renewal->exists && $renewal->bankTransferOrderItems()->whereHas('order', fn ($query) => $query
                    ->whereIn('status', ['pending', 'submitted', 'observed']))->exists()) {
                    throw ValidationException::withMessages([
                        'player_ids' => "{$player->full_name} tiene una orden de transferencia abierta. Anulala antes de registrar otro medio de pago.",
                    ]);
                }
                $currentPaid = (float) ($renewal->amount_paid ?: 0);
                $balance = max(0, (float) $membership->amount - $currentPaid);
                if ($amountPerPlayer > $balance) {
                    throw ValidationException::withMessages([
                        'payment_amount' => 'El pago de '.number_format($amountPerPlayer, 2, ',', '.')." supera el saldo de {$player->full_name}: ".number_format($balance, 2, ',', '.'),
                    ]);
                }
                $newPaid = $currentPaid + $amountPerPlayer;
                $fullyPaid = $newPaid >= (float) $membership->amount;

                $renewal->fill([
                    'club_id' => $preservePlayerClub ? $player->club_id : $club?->id,
                    'amount_due' => $membership->amount,
                    'amount_paid' => $newPaid,
                    'status' => $fullyPaid ? 'paid' : 'partial',
                    'enabled_to_compete' => $fullyPaid,
                    'paid_at' => $fullyPaid ? now() : null,
                ])->save();
                $renewal->payments()->create([
                    'payer_type' => in_array($payerType, ['club', 'federation'], true) ? $payerType : 'player',
                    'payer_id' => in_array($payerType, ['club', 'federation'], true) ? $payerId : $player->id,
                    'amount' => $amountPerPlayer,
                    'method' => 'manual',
                    'manual_method' => $manualMethod,
                    'external_reference' => $reference,
                    'notes' => $notes,
                    'status' => 'approved',
                    'recorded_by' => Auth::id(),
                    'paid_at' => now(),
                ]);
                $player->update(array_filter([
                    'club_id' => $preservePlayerClub ? $player->club_id : $club?->id,
                    'is_active' => true,
                    'is_enabled_to_compete' => $fullyPaid ? true : null,
                ], fn ($value) => $value !== null));

                $renewal->setAttribute('operation_label', $fullyPaid ? 'Pago total' : 'Pago parcial');

                return $renewal;
            });
        });

        $this->sendConfirmation($membership, $renewals);
        AdminNotifier::sendBulk(
            $renewals,
            'registró y acreditó el pago manual de',
            'player.full_name',
            'membresías',
        );

        return $renewals;
    }

    public function sendConfirmation(Membership $membership, Collection $renewals, ?User $recipient = null): void
    {
        $user = $recipient ?? Auth::user();

        if (! $user || blank($user->email) || $renewals->isEmpty()) {
            return;
        }

        $copies = AdminNotifier::recipientEmails()
            ? collect(AdminNotifier::recipientEmails())->reject(fn (string $email): bool => strcasecmp($email, $user->email) === 0)->values()->all()
            : [];

        try {
            Mail::to($user->email)
                ->cc($copies)
                ->send(new MembershipRenewalConfirmation($membership, $renewals, $user));
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar el comprobante de membresías.', [
                'message' => $exception->getMessage(),
                'user_id' => $user->id,
                'membership_id' => $membership->id,
            ]);
        }
    }

    public function expireOverdue(): int
    {
        $expiredPlayers = 0;

        Membership::query()
            ->where('active', true)
            ->where('year', now()->year)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today())
            ->each(function (Membership $membership) use (&$expiredPlayers): void {
                DB::transaction(function () use ($membership, &$expiredPlayers): void {
                    PlayerMembership::query()
                        ->where('membership_id', $membership->id)
                        ->where('status', '!=', 'paid')
                        ->where('institutionally_guaranteed', false)
                        ->update(['status' => 'expired', 'enabled_to_compete' => false]);

                    $renewedPlayerIds = PlayerMembership::query()
                        ->where('membership_id', $membership->id)
                        ->where('status', 'paid')
                        ->where('enabled_to_compete', true)
                        ->pluck('player_id');

                    $expiredPlayers += Player::query()
                        ->where('discipline_id', $membership->discipline_id)
                        ->whereNotIn('id', $renewedPlayerIds)
                        ->where('is_enabled_to_compete', true)
                        ->update(['is_enabled_to_compete' => false]);
                });
            });

        return $expiredPlayers;
    }
}
