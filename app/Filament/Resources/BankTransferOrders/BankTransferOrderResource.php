<?php

namespace App\Filament\Resources\BankTransferOrders;

use App\Filament\Resources\BankTransferOrders\Pages\EditBankTransferOrder;
use App\Filament\Resources\BankTransferOrders\Pages\ListBankTransferOrders;
use App\Models\BankTransferOrder;
use App\Models\BankTransferSetting;
use App\Services\AdminNotifier;
use App\Services\BankTransferService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use UnitEnum;

class BankTransferOrderResource extends Resource
{
    protected static ?string $model = BankTransferOrder::class;
    protected static string|UnitEnum|null $navigationGroup = 'Configuración General';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $navigationLabel = 'Pagos por transferencia';
    protected static ?string $modelLabel = 'orden de transferencia';
    protected static ?string $pluralModelLabel = 'pagos por transferencia';
    protected static ?int $navigationSort = 17;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['club', 'discipline', 'user']);

        return Auth::user()?->hasRole('super-admin') ? $query : $query->where('user_id', Auth::id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return Auth::check();
    }

    public static function canView($record): bool
    {
        return (Auth::user()?->hasRole('super-admin') ?? false) || (int) $record->user_id === (int) Auth::id();
    }

    public static function canEdit($record): bool
    {
        if (Auth::user()?->hasRole('super-admin')) {
            return true;
        }

        return (int) $record->user_id === (int) Auth::id()
            && in_array($record->status, ['pending', 'observed'], true);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $setting = BankTransferSetting::active();

        return $schema->components([
            Section::make('Orden de pago')->schema([
                Placeholder::make('code_display')->label('Orden')->content(fn (?BankTransferOrder $record): string => $record?->code ?? '—'),
                Placeholder::make('amount_display')->label('Importe exacto')->content(fn (?BankTransferOrder $record): string => '$ '.number_format((float) $record?->amount, 2, ',', '.')),
                Placeholder::make('expires_display')->label('Vencimiento')->content(fn (?BankTransferOrder $record): string => $record?->expires_at?->format('d/m/Y') ?? '—'),
                Placeholder::make('status_display')->label('Estado')->content(fn (?BankTransferOrder $record): string => self::statusLabels()[$record?->status] ?? '—'),
            ])->columns(4),
            Section::make('Cuenta de destino')->schema([
                Placeholder::make('holder')->label('Titular')->content($setting?->account_holder ?? 'Sin configurar'),
                Placeholder::make('tax')->label('CUIT')->content($setting?->tax_id ?? '—'),
                Placeholder::make('bank')->label('Banco')->content($setting?->bank_name ?? '—'),
                Placeholder::make('cbu_display')->label('CBU')->content($setting?->cbu ?? '—'),
                Placeholder::make('alias_display')->label('Alias')->content($setting?->alias ?? '—'),
                Placeholder::make('instructions_display')->label('Instrucciones')->content($setting?->instructions ?? 'Transferí el importe exacto e indicá el número de orden.')->columnSpanFull(),
            ])->columns(3),
            Section::make('Conceptos incluidos')->schema([
                Repeater::make('items')->relationship()->hiddenLabel()->schema([
                    TextInput::make('description')->label('Concepto')->disabled()->dehydrated(false),
                    TextInput::make('amount')->label('Importe')->prefix('$')->disabled()->dehydrated(false),
                ])->columns(2)->addable(false)->deletable(false)->reorderable(false)->dehydrated(false),
            ]),
            Section::make('Comprobante')->schema([
                FileUpload::make('receipt_path')->label('Comprobante')->disk('public_path')->directory('pagos/transferencias')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->openable()->downloadable()->required(),
                DatePicker::make('transferred_at')->label('Fecha de transferencia')->required(),
                TextInput::make('transferred_amount')->label('Importe transferido')->numeric()->prefix('$')->required(),
                TextInput::make('origin_bank')->label('Banco de origen')->required(),
                TextInput::make('origin_holder')->label('Titular de origen')->required(),
                TextInput::make('transaction_number')->label('Número de operación'),
                Textarea::make('submitter_notes')->label('Observaciones')->columnSpanFull(),
                Textarea::make('review_notes')->label('Observación de Tesorería')->disabled()->dehydrated(false)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('code')->label('Orden')->searchable()->copyable(),
            TextColumn::make('purpose')->label('Concepto')->formatStateUsing(fn (string $state): string => $state === 'membership' ? 'Membresías' : 'Inscripción'),
            TextColumn::make('club.name')->label('Club')->placeholder('Independiente')->searchable(),
            TextColumn::make('discipline.name')->label('Disciplina'),
            TextColumn::make('amount')->label('Total')->money('ARS'),
            TextColumn::make('expires_at')->label('Vence')->date('d/m/Y'),
            TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn (string $state): string => self::statusLabels()[$state] ?? $state)
                ->color(fn (string $state): string => match ($state) {
                    'approved' => 'success', 'submitted' => 'info', 'observed' => 'warning',
                    'rejected', 'expired', 'cancelled' => 'danger', default => 'gray',
                }),
        ])->filters([
            SelectFilter::make('status')->label('Estado')->options(self::statusLabels()),
            SelectFilter::make('purpose')->label('Concepto')->options(['membership' => 'Membresías', 'tournament_registration' => 'Inscripción']),
        ])->recordActions([
            EditAction::make()->label('Ver / cargar comprobante'),
            self::shareByWhatsAppAction(),
            Action::make('download')->label('Orden PDF')->icon('heroicon-o-arrow-down-tray')
                ->action(function (BankTransferOrder $record) {
                    $record->load(['items.payable', 'club', 'discipline', 'user']);

                    return response()->streamDownload(
                        fn () => print(Pdf::loadView('pdf.bank-transfer-order', [
                            'order' => $record,
                            'setting' => BankTransferSetting::active(),
                        ])->output()),
                        "orden-{$record->code}.pdf",
                    );
                }),
            Action::make('approve')->label('Aprobar')->icon('heroicon-o-check-circle')->color('success')
                ->requiresConfirmation()->visible(fn (BankTransferOrder $record): bool => (Auth::user()?->hasRole('super-admin') ?? false) && in_array($record->status, ['submitted', 'observed'], true))
                ->action(function (BankTransferOrder $record): void {
                    app(BankTransferService::class)->approve($record);
                }),
            Action::make('observe')->label('Observar')->icon('heroicon-o-exclamation-triangle')->color('warning')
                ->schema([Textarea::make('review_notes')->label('Motivo')->required()])
                ->visible(fn (BankTransferOrder $record): bool => (Auth::user()?->hasRole('super-admin') ?? false) && $record->status === 'submitted')
                ->action(function (BankTransferOrder $record, array $data): void {
                    $record->update(['status' => 'observed', 'review_notes' => $data['review_notes'], 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
                    AdminNotifier::send(null, $record, 'observó', 'code', 'Pagos por transferencia');
                }),
            Action::make('reject')->label('Rechazar')->icon('heroicon-o-x-circle')->color('danger')
                ->requiresConfirmation()->schema([Textarea::make('review_notes')->label('Motivo')->required()])
                ->visible(fn (BankTransferOrder $record): bool => (Auth::user()?->hasRole('super-admin') ?? false) && in_array($record->status, ['submitted', 'observed'], true))
                ->action(function (BankTransferOrder $record, array $data): void {
                    app(BankTransferService::class)->reject($record, $data['review_notes']);
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBankTransferOrders::route('/'),
            'edit' => EditBankTransferOrder::route('/{record}/edit'),
        ];
    }

    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pendiente de comprobante', 'submitted' => 'En revisión',
            'observed' => 'Observada', 'approved' => 'Aprobada', 'rejected' => 'Rechazada',
            'expired' => 'Vencida', 'cancelled' => 'Anulada',
        ];
    }

    public static function shareByWhatsAppAction(): Action
    {
        return Action::make('shareWhatsApp')
            ->label(new HtmlString(
                '<span style="display:inline-flex;align-items:center;gap:.45rem">'
                .'<svg aria-hidden="true" viewBox="0 0 32 32" width="18" height="18" fill="currentColor">'
                .'<path d="M16 3A13 13 0 0 0 4.78 22.54L3 29l6.62-1.74A13 13 0 1 0 16 3Zm0 23.62c-2.1 0-4.14-.62-5.88-1.78l-.42-.25-3.93 1.03 1.05-3.82-.27-.44A10.62 10.62 0 1 1 16 26.62Zm5.82-7.95c-.32-.16-1.88-.93-2.17-1.03-.29-.11-.5-.16-.71.16-.21.32-.82 1.03-1 1.24-.19.21-.37.24-.69.08-.32-.16-1.34-.49-2.55-1.58-.94-.84-1.58-1.88-1.76-2.2-.19-.32-.02-.49.14-.65.14-.14.32-.37.48-.56.16-.19.21-.32.32-.53.11-.21.05-.4-.03-.56-.08-.16-.71-1.72-.98-2.35-.26-.62-.52-.54-.71-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.11 1.09-1.11 2.65 0 1.56 1.14 3.07 1.3 3.28.16.21 2.24 3.42 5.43 4.8.76.33 1.35.52 1.81.67.76.24 1.46.21 2.01.13.61-.09 1.88-.77 2.14-1.51.26-.74.26-1.38.19-1.51-.08-.13-.29-.21-.61-.37Z"/>'
                .'</svg><span>Compartir por WhatsApp</span></span>',
            ))
            ->color('success')
            ->url(fn (BankTransferOrder $record): string => self::whatsAppShareUrl($record))
            ->openUrlInNewTab();
    }

    public static function whatsAppShareUrl(BankTransferOrder $order): string
    {
        $order->loadMissing('items');
        $pdfUrl = URL::temporarySignedRoute(
            'bank-transfer-orders.pdf',
            now()->addDays(7),
            ['order' => $order],
        );
        $concept = $order->purpose === 'membership' ? 'membresías anuales' : 'inscripción al torneo';
        $message = implode("\n", [
            'Federación Argentina de Billar',
            '',
            "Orden: {$order->code}",
            "Concepto: {$concept}",
            'Importe: $ '.number_format((float) $order->amount, 2, ',', '.'),
            'Vencimiento: '.($order->expires_at?->format('d/m/Y') ?? 'Sin vencimiento'),
            '',
            'Descargar orden de pago:',
            $pdfUrl,
        ]);

        return 'https://wa.me/?text='.rawurlencode($message);
    }
}
