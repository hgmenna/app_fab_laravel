<?php

namespace App\Filament\Resources\MembershipPayments;

use App\Filament\Resources\MembershipPayments\Pages\EditMembershipPayment;
use App\Filament\Resources\MembershipPayments\Pages\ListMembershipPayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class MembershipPaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static string|UnitEnum|null $navigationGroup = 'Tesorería';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;
    protected static ?string $navigationLabel = 'Movimientos de membresías';
    protected static ?string $modelLabel = 'movimiento de membresía';
    protected static ?string $pluralModelLabel = 'movimientos de membresías';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Auth::user()?->canGloballyOrInAnyDiscipline('RenewClubMemberships') ?? false;
    }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool
    {
        return Auth::user()?->canGloballyOrInAnyDiscipline('RenewClubMemberships') ?? false;
    }
    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'membership.player.club', 'membership.membership.discipline', 'payer', 'recorder',
        ]);
        $user = Auth::user();
        if (! $user || $user->hasRole('super-admin') || ! $user->hasActiveDisciplineAssignments()) {
            return $query;
        }
        return $query->whereHas('membership.membership', fn (Builder $membership) => $membership
            ->whereIn('discipline_id', $user->allowedDisciplineIds('RenewClubMemberships') ?: [-1]));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Movimiento')->columns(3)->schema([
                Placeholder::make('player')->label('Jugador')->content(fn (?Payment $record) => $record?->membership?->player?->full_name ?? '—'),
                Placeholder::make('amount_display')->label('Importe')->content(fn (?Payment $record) => '$ '.number_format((float)$record?->amount,2,',','.')),
                Placeholder::make('date_display')->label('Fecha')->content(fn (?Payment $record) => ($record?->paid_at ?? $record?->created_at)?->format('d/m/Y H:i') ?? '—'),
                Textarea::make('notes')->label('Observación')->placeholder('Ej.: efectivo entregado a Juan Pérez')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('membership.player.full_name')->label('Jugador')->searchable(['first_name','last_name']),
            TextColumn::make('membership.membership.discipline.name')->label('Disciplina'),
            TextColumn::make('payer_name')->label('Pagador')->state(fn (Payment $record): string => $record->payer?->name ?? $record->payer?->full_name ?? 'Jugador'),
            TextColumn::make('manual_method')->label('Medio')->formatStateUsing(fn ($state, Payment $record): string => match($state){'cash'=>'Efectivo','bank_transfer'=>'Transferencia','other'=>'Otro medio',default=>ucfirst($record->method)}),
            TextColumn::make('external_reference')->label('Referencia')->placeholder('—'),
            TextColumn::make('amount')->label('Importe')->money('ARS')->alignEnd(),
            TextColumn::make('notes')->label('Observación')->limit(45)->placeholder('—')->wrap(),
        ])->filters([
            SelectFilter::make('method')->label('Tipo')->options(['manual'=>'Manual','pluspagos'=>'PlusPagos','mercadopago'=>'Mercado Pago']),
        ])->recordActions([
            EditAction::make()->label('Editar observación')->icon('heroicon-o-pencil-square')->iconButton()->tooltip('Editar observación'),
            Action::make('print')->label('Ver / imprimir')->icon('heroicon-o-printer')->iconButton()->tooltip('Ver o imprimir comprobante')->openUrlInNewTab()
                ->url(fn (Payment $record): string => URL::temporarySignedRoute('membership-payments.pdf', now()->addDays(7), ['payment'=>$record])),
            Action::make('share')->label('Compartir')->icon('heroicon-o-share')->iconButton()->color('success')->tooltip('Compartir por WhatsApp')->openUrlInNewTab()
                ->url(function (Payment $record): string {
                    $url = URL::temporarySignedRoute('membership-payments.pdf', now()->addDays(7), ['payment'=>$record]);
                    $message = "Comprobante de pago de membresía\n{$record->membership?->player?->full_name}\nImporte: $ ".number_format((float)$record->amount,2,',','.')."\n{$url}";
                    return 'https://wa.me/?text='.rawurlencode($message);
                }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index'=>ListMembershipPayments::route('/'),'edit'=>EditMembershipPayment::route('/{record}/edit')];
    }
}
