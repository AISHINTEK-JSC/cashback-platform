<?php

namespace App\Filament\Resources\Withdrawals\Schemas;

use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
use App\Support\VietQr;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
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
                Section::make('Quét mã để chuyển khoản')
                    ->description('Mở app ngân hàng và quét mã: đã điền sẵn ngân hàng, số tài khoản, số tiền thực chuyển và nội dung là mã yêu cầu. Kiểm tra lại tên chủ tài khoản trước khi xác nhận.')
                    ->visible(fn (Withdrawal $record) => in_array($record->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing], true))
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('vietqr')
                            ->hiddenLabel()
                            ->state(fn (Withdrawal $record) => VietQr::forWithdrawal($record))
                            ->imageHeight(320)
                            ->checkFileExistence(false)
                            ->extraImgAttributes(['alt' => 'Mã VietQR chuyển khoản', 'loading' => 'lazy'])
                            ->visible(fn (Withdrawal $record) => VietQr::forWithdrawal($record) !== null),
                        TextEntry::make('vietqr_unavailable')
                            ->hiddenLabel()
                            ->state('Không tạo được mã VietQR cho tài khoản này (ngân hàng/ví không có trong danh sách VietQR hoặc số tài khoản quá dài). Hãy chuyển khoản thủ công theo thông tin bên trên.')
                            ->color('warning')
                            ->visible(fn (Withdrawal $record) => VietQr::forWithdrawal($record) === null),
                        Group::make([
                            TextEntry::make('transfer_amount')->label('Số tiền chuyển')->state(fn (Withdrawal $record) => vnd($record->net_amount))->copyable()->copyableState(fn (Withdrawal $record) => (string) $record->net_amount)->weight('bold'),
                            TextEntry::make('transfer_content')->label('Nội dung chuyển khoản')->state(fn (Withdrawal $record) => $record->code)->copyable()->fontFamily('mono'),
                        ]),
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
