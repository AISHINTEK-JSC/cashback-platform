<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Services\ReferralService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn ($state) => vnd($state);

        return $schema
            ->components([
                Section::make('Tài khoản')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Họ tên'),
                        TextEntry::make('email')->label('Email')->copyable(),
                        TextEntry::make('phone')->label('SĐT')->placeholder('-'),
                        TextEntry::make('referral_code')->label('Mã mời')->copyable(),
                        TextEntry::make('referrer.name')->label('Người mời (F1 của họ)')->placeholder('-'),
                        TextEntry::make('level.name')->label('Hạng')->placeholder('-'),
                        TextEntry::make('role')->label('Vai trò')->badge(),
                        TextEntry::make('status')->label('Trạng thái')->badge(),
                        TextEntry::make('created_at')->label('Ngày tham gia')->dateTime('d/m/Y H:i'),
                        TextEntry::make('last_login_at')->label('Đăng nhập gần nhất')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('last_login_ip')->label('IP')->placeholder('-'),
                    ]),
                Section::make('Ví')
                    ->columns(5)
                    ->schema([
                        TextEntry::make('wallet.pending_balance')->label('Chờ duyệt')->formatStateUsing($money),
                        TextEntry::make('wallet.available_balance')->label('Khả dụng')->formatStateUsing($money),
                        TextEntry::make('wallet.frozen_balance')->label('Đang rút')->formatStateUsing($money),
                        TextEntry::make('wallet.total_earned')->label('Tổng đã nhận')->formatStateUsing($money),
                        TextEntry::make('wallet.total_withdrawn')->label('Tổng đã rút')->formatStateUsing($money),
                    ]),
                Section::make('Giới thiệu')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('f1_count')->label('Số F1')->state(fn (User $record) => app(ReferralService::class)->summary($record)['f1_count']),
                        TextEntry::make('f2_count')->label('Số F2')->state(fn (User $record) => app(ReferralService::class)->summary($record)['f2_count']),
                        TextEntry::make('f1_earned')->label('Hoa hồng F1')->state(fn (User $record) => vnd(app(ReferralService::class)->summary($record)['f1_earned'])),
                        TextEntry::make('f2_earned')->label('Hoa hồng F2')->state(fn (User $record) => vnd(app(ReferralService::class)->summary($record)['f2_earned'])),
                    ]),
                Section::make('Tài khoản nhận tiền')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('bankAccounts')
                            ->hiddenLabel()
                            ->columns(4)
                            ->schema([
                                TextEntry::make('bank_name')->label('Ngân hàng'),
                                TextEntry::make('account_number')->label('Số TK')->copyable(),
                                TextEntry::make('account_name')->label('Chủ TK'),
                                TextEntry::make('created_at')->label('Thêm lúc')->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
            ]);
    }
}
