<?php

namespace App\Filament\Resources\Memberships;

use App\Filament\Resources\Concerns\ScopesToUserDisciplines;
use App\Filament\Resources\Memberships\Pages\CreateMembership;
use App\Filament\Resources\Memberships\Pages\EditMembership;
use App\Filament\Resources\Memberships\Pages\ListMemberships;
use App\Filament\Resources\Memberships\Schemas\MembershipForm;
use App\Filament\Resources\Memberships\Tables\MembershipsTable;
use App\Models\Membership;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MembershipResource extends Resource
{
    use ScopesToUserDisciplines;

    protected static ?string $model = Membership::class;

    protected static string|UnitEnum|null $navigationGroup = 'Tesorería';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Membresías anuales';

    protected static ?string $modelLabel = 'membresía anual';

    protected static ?string $pluralModelLabel = 'membresías anuales';

    protected static ?int $navigationSort = 1;

    protected static function disciplineViewPermission(): string
    {
        return 'RenewClubMemberships';
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->canGloballyOrInAnyDiscipline('RenewClubMemberships') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasRole('super-admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->hasRole('super-admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->hasRole('super-admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return MembershipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemberships::route('/'),
            'create' => CreateMembership::route('/create'),
            'edit' => EditMembership::route('/{record}/edit'),
        ];
    }
}
