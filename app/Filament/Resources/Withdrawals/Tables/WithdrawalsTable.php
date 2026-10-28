<?php

namespace App\Filament\Resources\Withdrawals\Tables;

use App\Enums\WithdrawalMethod;
use App\Enums\WithdrawalStatus;
use App\Filament\Support\WithdrawalActions;
use App\Models\Withdrawal;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WithdrawalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->weight('medium'),
                TextColumn::make('user.name')->label('Thành viên')->searchable()->description(fn (Withdrawal $record) => $record->user?->email),
                TextColumn::make('destination')
                    ->label('Nhận về')
                    ->state(fn (Withdrawal $record) => $record->destinationName())
                    ->description(fn (Withdrawal $record) => $record->method === WithdrawalMethod::Bank ? $record->account_number.' - '.$record->account_name : $record->destinationNumber()),
                TextColumn::make('amount')->label('Số tiền')->formatStateUsing(fn ($state) => vnd($state))->sortable()->alignEnd(),
                TextColumn::make('net_amount')->label('Thực chuyển')->formatStateUsing(fn ($state) => vnd($state))->alignEnd(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('reviewer.name')->label('Người xử lý')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bank_txn_ref')->label('Mã GD')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(WithdrawalStatus::class)->multiple(),
                SelectFilter::make('method')->label('Cách nhận')->options(WithdrawalMethod::class),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(WithdrawalActions::all()),
            ]);
    }
}
