<?php

namespace App\Filament\Resources\Withdrawals;

use App\Enums\WithdrawalStatus;
use App\Filament\Resources\Withdrawals\Pages\ListWithdrawals;
use App\Filament\Resources\Withdrawals\Pages\ViewWithdrawal;
use App\Filament\Resources\Withdrawals\Schemas\WithdrawalInfolist;
use App\Filament\Resources\Withdrawals\Tables\WithdrawalsTable;
use App\Filament\Support\NavigationGroup;
use App\Models\Withdrawal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Operations;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'yêu cầu rút tiền';

    protected static ?string $pluralModelLabel = 'Rút tiền';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationBadge(): ?string
    {
        $count = Withdrawal::query()->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return WithdrawalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WithdrawalsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithdrawals::route('/'),
            'view' => ViewWithdrawal::route('/{record}'),
        ];
    }
}
