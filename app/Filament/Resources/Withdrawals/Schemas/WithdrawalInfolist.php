<?php

namespace App\Filament\Resources\Withdrawals\Schemas;

use App\Models\Withdrawal;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WithdrawalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Yêu cầu')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('code')->label('Mã'),
                        TextEntry::make('status')->label('Trạng thái')->badge(),
                        TextEntry::make('user.name')->label('Thành viên')->helperText(fn (Withdrawal $record) => $record->user?->email),
                        TextEntry::make('request_ip')->label('IP yêu cầu')->placeholder('-'),
                        TextEntry::make('amount')->label('Số tiền')->formatStateUsing(fn ($state) => vnd($state)),
                        TextEntry::make('fee')->label('Phí')->formatStateUsing(fn ($state) => vnd($state)),
                        TextEntry::make('net_amount')->label('Thực chuyển')->formatStateUsing(fn ($state) => vnd($state))->weight('bold'),
                        TextEntry::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i'),
                    ]),
                Section::make('Tài khoản nhận (snapshot lúc tạo yêu cầu)')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('bank_name')->label('Ngân hàng'),
                        TextEntry::make('account_number')->label('Số tài khoản')->copyable(),
                        TextEntry::make('account_name')->label('Chủ tài khoản')->copyable(),
                    ]),
                Section::make('Xử lý')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewer.name')->label('Người xử lý')->placeholder('-'),
                        TextEntry::make('reviewed_at')->label('Thời gian')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('bank_txn_ref')->label('Mã giao dịch')->placeholder('-'),
                        TextEntry::make('paid_at')->label('Ngày thanh toán')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('reject_reason')->label('Lý do từ chối')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
