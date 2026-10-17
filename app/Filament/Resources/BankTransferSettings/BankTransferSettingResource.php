<?php

namespace App\Filament\Resources\BankTransferSettings;

use App\Filament\Resources\BankTransferSettings\Pages\ListBankTransferSettings;
use App\Models\BankTransferSetting;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BankTransferSettingResource extends Resource
{
    protected static ?string $model = BankTransferSetting::class;
    protected static string|UnitEnum|null $navigationGroup = 'Tesorería';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static ?string $navigationLabel = 'Cuenta para transferencias';
    protected static ?string $modelLabel = 'cuenta para transferencias';
    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super-admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return (Auth::user()?->hasRole('super-admin') ?? false) && ! BankTransferSetting::query()->exists();
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->hasRole('super-admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('account_holder')->label('Titular')->required()->maxLength(255),
            TextInput::make('tax_id')->label('CUIT')->required()->maxLength(20),
            TextInput::make('bank_name')->label('Banco')->default('Banco Macro SA')->required(),
            TextInput::make('account_type')->label('Tipo de cuenta'),
            TextInput::make('account_number')->label('Número de cuenta'),
            TextInput::make('cbu')->label('CBU')->length(22)->numeric()->required(),
            TextInput::make('alias')->label('Alias')->maxLength(255),
            TextInput::make('expires_in_days')->label('Vigencia de la orden (días)')->numeric()->minValue(1)->default(5)->required(),
            TextInput::make('treasury_email')->label('Correo de Tesorería')->email(),
            Textarea::make('instructions')->label('Instrucciones para el pagador')->columnSpanFull(),
            Toggle::make('is_active')->label('Habilitada')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('account_holder')->label('Titular'),
            TextColumn::make('bank_name')->label('Banco'),
            TextColumn::make('cbu')->label('CBU')->copyable(),
            TextColumn::make('alias')->label('Alias')->copyable(),
            TextColumn::make('expires_in_days')->label('Vigencia')->suffix(' días'),
            IconColumn::make('is_active')->label('Activa')->boolean(),
        ])->recordActions([
            EditAction::make()->label('Editar cuenta')->icon('heroicon-o-pencil-square')
                ->iconButton()->tooltip('Editar cuenta receptora'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBankTransferSettings::route('/')];
    }
}
