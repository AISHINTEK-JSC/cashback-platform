<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn ($state) => vnd($state);

        return $schema
            ->components([
                Section::make('Đơn hàng')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('platform_order_id')->label('Mã đơn')->copyable(),
                        TextEntry::make('platform.name')->label('Sàn'),
                        TextEntry::make('status')->label('Trạng thái')->badge(),
                        TextEntry::make('user.name')->label('Thành viên')->placeholder('Chưa khớp sub_id'),
                        TextEntry::make('sub_id')->label('Sub ID')->placeholder('-'),
                        TextEntry::make('platform_status')->label('Trạng thái sàn')->placeholder('-'),
                        TextEntry::make('order_amount')->label('Giá trị đơn')->formatStateUsing($money),
                        TextEntry::make('commission_amount')->label('Hoa hồng sàn trả')->formatStateUsing($money),
                        TextEntry::make('cashback_amount')->label('Hoàn cho người mua')->formatStateUsing($money),
                        TextEntry::make('purchased_at')->label('Ngày mua')->dateTime('d/m/Y H:i'),
                        TextEntry::make('confirmed_at')->label('Sàn xác nhận')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('approved_at')->label('Duyệt')->dateTime('d/m/Y H:i')->placeholder('-'),
                    ]),
                Section::make('Chia tiền')
                    ->schema([
                        RepeatableEntry::make('rewards')
                            ->hiddenLabel()
                            ->columns(5)
                            ->schema([
                                TextEntry::make('type')->label('Loại')->badge(),
                                TextEntry::make('user.name')->label('Người nhận'),
                                TextEntry::make('rate')->label('Tỷ lệ')->suffix('%'),
                                TextEntry::make('amount')->label('Số tiền')->formatStateUsing($money),
                                TextEntry::make('status')->label('Trạng thái')->badge(),
                            ]),
                    ]),
                Section::make('Sản phẩm')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->columns(5)
                            ->schema([
                                TextEntry::make('item_name')->label('Sản phẩm')->columnSpan(2),
                                TextEntry::make('category_code')->label('Ngành')->placeholder('-'),
                                TextEntry::make('quantity')->label('SL'),
                                TextEntry::make('commission_amount')->label('Hoa hồng')->formatStateUsing($money),
                            ]),
                    ]),
                Section::make('Lịch sử trạng thái')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('statusHistories')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('to_status')->label('Trạng thái')->formatStateUsing(fn (string $state) => OrderStatus::tryFrom($state)?->getLabel() ?? $state),
                                TextEntry::make('source')->label('Nguồn')->badge(),
                                TextEntry::make('note')->label('Ghi chú')->placeholder('-'),
                                TextEntry::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
                Section::make('Dữ liệu gốc từ sàn')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('raw_payload')->hiddenLabel()->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->fontFamily('mono')->prose(),
                    ]),
            ]);
    }
}
