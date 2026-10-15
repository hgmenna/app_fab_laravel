<?php

namespace App\Services;

use App\Mail\MembershipRenewalConfirmation;
use App\Models\Club;
use App\Models\Membership;
use App\Models\Player;
use App\Models\PlayerMembership;
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

    public function renew(Membership $membership, Collection $players, ?Club $club): Collection
    {
        if (! $membership->active) {
            throw ValidationException::withMessages([
                'membership' => 'La membresía anual seleccionada no está activa.',
            ]);
        }

        $renewals = DB::transaction(function () use ($membership, $players, $club): Collection {
            return $players->map(function (Player $player) use ($membership, $club): PlayerMembership {
                if ((int) $player->discipline_id !== (int) $membership->discipline_id) {
                    throw ValidationException::withMessages([
                        'player_ids' => "{$player->full_name} no pertenece a la disciplina seleccionada.",
                    ]);
                }

                $playerMembership = PlayerMembership::query()->firstOrNew([
                    'player_id' => $player->id,
                    'membership_id' => $membership->id,
                ]);
                $operation = $playerMembership->exists ? 'Renovación' : 'Nueva membresía';
                $playerMembership->fill([
                    'club_id' => $club?->id,
                    'amount_due' => $membership->amount,
                    'amount_paid' => $membership->amount,
                    'status' => 'paid',
                    'enabled_to_compete' => true,
                    'paid_at' => now(),
                ])->save();

                $playerMembership->payments()->updateOrCreate(
                    ['external_reference' => "RENOVACION-{$membership->year}-{$player->id}"],
                    [
                        'payer_type' => $club ? 'club' : 'player',
                        'payer_id' => $club?->id ?? $player->id,
                        'amount' => $membership->amount,
                        'method' => 'manual',
                        'status' => 'approved',
                    ],
                );

                $player->update([
                    'club_id' => $club?->id,
                    'is_active' => true,
                    'is_enabled_to_compete' => true,
                ]);

                $playerMembership->setAttribute('operation_label', $operation);

                return $playerMembership;
            });
        });

        $this->sendConfirmation($membership, $renewals);

        return $renewals;
    }

    private function sendConfirmation(Membership $membership, Collection $renewals): void
    {
        $user = Auth::user();

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
