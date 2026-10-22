<?php

namespace App\Filament\Resources\WalletTransactions;

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Filament\Resources\WalletTransactions\Pages\ListWalletTransactions;
use App\Filament\Support\NavigationGroup;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Sổ cái ví, chỉ đọc.
 */
class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'bút toán';

    protected static ?string $pluralModelLabel = 'Sổ cái ví';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i:s'),
                TextColumn::make('user.name')->label('Thành viên')->searchable(),
                TextColumn::make('type')->label('Loại')->badge(),
                TextColumn::make('bucket')->label('Số dư')->badge(),
                TextColumn::make('amount')->label('Số tiền')->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').vnd($state))->color(fn (int $state) => $state >= 0 ? 'success' : 'danger')->alignEnd(),
                TextColumn::make('balance_after')->label('Số dư sau')->formatStateUsing(fn ($state) => vnd($state))->alignEnd(),
                TextColumn::make('reference_type')->label('Tham chiếu')->formatStateUsing(fn (WalletTransaction $record) => $record->reference_type ? "{$record->reference_type}#{$record->reference_id}" : '-'),
                TextColumn::make('description')->label('Nội dung')->limit(50)->searchable(),
                TextColumn::make('creator.name')->label('Người tạo')->placeholder('Hệ thống')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Loại')->options(WalletTransactionType::class),
                SelectFilter::make('bucket')->label('Số dư')->options(WalletBucket::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
        ];
    }
}
