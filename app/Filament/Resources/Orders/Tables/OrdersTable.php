<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Support\OrderActions;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('purchased_at', 'desc')
            ->columns([
                TextColumn::make('platform_order_id')->label('Mã đơn')->searchable()->copyable()->weight('medium'),
                TextColumn::make('platform.name')->label('Sàn')->badge()->color('gray'),
                TextColumn::make('user.name')->label('Thành viên')->searchable()->placeholder('Chưa khớp')->description(fn (Order $record) => $record->user?->email),
                TextColumn::make('order_amount')->label('Giá trị')->formatStateUsing(fn ($state) => vnd($state))->sortable()->alignEnd(),
                TextColumn::make('commission_amount')->label('Hoa hồng sàn')->formatStateUsing(fn ($state) => vnd($state))->sortable()->alignEnd(),
                TextColumn::make('cashback_amount')->label('Hoàn cho khách')->formatStateUsing(fn ($state) => vnd($state))->alignEnd(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('platform_status')->label('TT sàn')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sub_id')->label('Sub ID')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchased_at')->label('Ngày mua')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('last_synced_at')->label('Đồng bộ lúc')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(OrderStatus::class)->multiple(),
                SelectFilter::make('platform_id')->label('Sàn')->relationship('platform', 'name'),
                Filter::make('unmatched')->label('Chưa khớp thành viên')->query(fn (Builder $query) => $query->whereNull('user_id')),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(OrderActions::all()),
            ]);
    }
}
