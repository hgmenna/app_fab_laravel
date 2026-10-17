<?php

namespace App\Filament\Resources\MembershipAccounts;

use App\Filament\Resources\MembershipAccounts\Pages\ListMembershipAccounts;
use App\Models\Discipline;
use App\Models\PlayerMembership;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class MembershipAccountResource extends Resource
{
    protected static ?string $model = PlayerMembership::class;
    protected static string|UnitEnum|null $navigationGroup = 'Tesorería';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;
    protected static ?string $navigationLabel = 'Cuenta corriente de membresías';
    protected static ?string $modelLabel = 'cuenta de membresía';
    protected static ?string $pluralModelLabel = 'cuentas corrientes de membresías';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Auth::user()?->canGloballyOrInAnyDiscipline('RenewClubMemberships') ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'player.club', 'player.category', 'membership.discipline', 'club',
            'payments' => fn ($payments) => $payments->with(['payer', 'recorder'])->latest(),
        ]);
        $user = Auth::user();
        if (! $user || $user->hasRole('super-admin') || ! $user->hasActiveDisciplineAssignments()) {
            return $query;
        }

        return $query->whereHas('membership', fn (Builder $membership) => $membership
            ->whereIn('discipline_id', $user->allowedDisciplineIds('RenewClubMemberships') ?: [-1]));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('membership.year')->label('Año')->sortable(),
                TextColumn::make('membership.discipline.name')->label('Disciplina')->searchable(),
                TextColumn::make('player.full_name')->label('Apellido y nombre')->searchable(['first_name', 'last_name']),
                TextColumn::make('club.name')->label('Club / sala')->placeholder('INDEPENDIENTE')->searchable(),
                TextColumn::make('amount_due')->label('Membresía')->money('ARS')->alignEnd(),
                TextColumn::make('amount_paid')->label('Abonado')->money('ARS')->alignEnd(),
                TextColumn::make('balance')->label('Saldo')->money('ARS')->alignEnd()
                    ->color(fn (PlayerMembership $record): string => $record->balance > 0 ? 'danger' : 'success'),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Pagada', 'partial' => 'Pago parcial', 'expired' => 'Vencida', default => 'Pendiente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success', 'partial' => 'warning', 'expired' => 'danger', default => 'gray',
                    }),
                TextColumn::make('payments_count')->label('Movimientos')->counts('payments')->alignCenter(),
                TextColumn::make('paid_at')->label('Cancelada el')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('year')->label('Año')
                    ->options(fn (): array => \App\Models\Membership::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $year): Builder => $query->whereHas('membership', fn (Builder $membership) => $membership->where('year', $year)),
                    )),
                SelectFilter::make('status')->label('Estado')->options([
                    'pending' => 'Pendiente', 'partial' => 'Pago parcial', 'paid' => 'Pagada', 'expired' => 'Vencida',
                ]),
                SelectFilter::make('club_id')->label('Club / sala')->relationship('club', 'name')->searchable()->preload(),
                SelectFilter::make('discipline_id')->label('Disciplina')
                    ->options(fn (): array => Discipline::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $id): Builder => $query->whereHas('membership', fn (Builder $membership) => $membership->where('discipline_id', $id)),
                    )),
            ])
            ->recordActions([
                Action::make('history')->label('Ver movimientos')->icon('heroicon-o-list-bullet')
                    ->iconButton()->color('info')->tooltip('Ver movimientos de la cuenta')
                    ->modalHeading(fn (PlayerMembership $record): string => 'Cuenta corriente · '.$record->player->full_name)
                    ->modalSubmitAction(false)->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (PlayerMembership $record) => view('filament.membership-account-history', [
                        'record' => $record->load(['payments.payer', 'payments.recorder']),
                    ])),
                Action::make('print')->label('Ver / imprimir cuenta')->icon('heroicon-o-printer')->iconButton()
                    ->tooltip('Ver o imprimir cuenta corriente')->openUrlInNewTab()
                    ->url(fn (PlayerMembership $record): string => URL::temporarySignedRoute(
                        'membership-accounts.pdf', now()->addDays(7), ['account' => $record]
                    )),
                Action::make('share')->label('Compartir cuenta')->icon('heroicon-o-share')->iconButton()->color('success')
                    ->tooltip('Compartir cuenta corriente por WhatsApp')->openUrlInNewTab()
                    ->url(function (PlayerMembership $record): string {
                        $url = URL::temporarySignedRoute('membership-accounts.pdf', now()->addDays(7), ['account' => $record]);
                        $message = "Cuenta corriente de membresía\n{$record->player->full_name}\nSaldo: $ ".number_format($record->balance, 2, ',', '.')."\n{$url}";
                        return 'https://wa.me/?text='.rawurlencode($message);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMembershipAccounts::route('/')];
    }
}
